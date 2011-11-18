<?php

require_once (t3lib_extMgm::extPath('nawork_uri') . '/lib/class.tx_naworkuri_helper.php');

class tx_naworkuri_cache {
	const TX_NAWORKURI_URI_TYPE_NORMAL = 0;
	const TX_NAWORKURI_URI_TYPE_OLD = 1;
	const TX_NAWORKURI_URI_TYPE_REDIRECT = 2;

	private $helper;

	/**
	 *
	 * @var tx_naworkuri_configReader
	 */
	private $config;
	private $timeout = 86400;

	/**
	 *
	 * @var t3lib_db
	 */
	private $db = null;

	/**
	 * Constructor
	 *
	 */
	public function __construct($config) {
		$this->helper = t3lib_div::makeInstance('tx_naworkuri_helper');
		$this->config = $config;
		$this->db = $GLOBALS['TYPO3_DB'];
		$this->db->store_lastBuiltQuery = 1;
	}

	/**
	 * Set the timeout for the url cache validity
	 *
	 * @param int $time Number of seconds the url should be valid, defaults to 86400 (= one day)
	 */
	public function setTimeout($time = 86400) {
		$this->timeout = $time;
//		$this->timeout = 0;
	}

	/**
	 * Find a url based on the parameters and the domain
	 *
	 * @param array $params
	 * @param string $domain
	 */
	public function findCachedUrl($params, $domain) {
		$uid = (int) $params['id'];
		$lang = (int) ($params['L'] ? $params['L'] : 0);
		unset($params['id']);
		unset($params['L']);
		/* evaluate the cache timeout */
		$pageRes = $this->db->exec_SELECTgetRows('*', $this->config->getPageTable(), 'uid=' . intval($uid));
		$page = $pageRes[0];
		if ($page['cache_timeout'] > 0) {
			$this->setTimeout($page['cache_timeout']);
		} elseif ($GLOBALS['TSFE']->config['config']['cache_period'] > 0) {
			$this->setTimeout($GLOBALS['TSFE']->config['config']['cache_period']);
		} else {
			$this->setTimeout(); // set to default, should be 86400 (24 hours)
		}
		/* if there is now be user logged in hidden or time controlled non visible pages should not return an url */
		$displayPageCondition = ' AND p.hidden=0 AND p.starttime < ' . time() . ' AND (p.endtime=0 OR p.endtime > ' . time() . ') ';
		if (tx_naworkuri_helper::isActiveBeUserSession()) {
			$displayPageCondition = '';
		}
		$urls = $this->db->exec_SELECTgetRows(
				'u.*', $this->config->getUriTable() . ' u, ' . $this->config->getPageTable() . ' p', 'u.page_uid=' . intval($uid) . ' AND sys_language_uid=' . intval($lang) . ' AND hash_params="' . md5(tx_naworkuri_helper::implode_parameters($params)) . '" AND u.deleted=0 AND u.domain="' . $domain . '" ' . $displayPageCondition . ' AND p.deleted=0 AND p.uid=u.page_uid AND type=0 AND ( u.tstamp > ' . (time() - $this->timeout) . ' OR locked=1 )', // lets find type 0 urls only from the cache
				'', '', '1'
		);
		if (is_array($urls) && count($urls) > 0) {
			return $urls[0]['path'];
		}
		return FALSE;
	}

	/**
	 * Find an existing url based on the page's id, language, parameters and domain.
	 * This is used to get an url that's cache time has expired but is a normal url.
	 *
	 * @param integer $id
	 * @param integer $language
	 * @param array $params
	 * @param string $domain
	 */
	public function findExistantUrl($page, $language, $params, $domain) {
		$urls = $this->db->exec_SELECTgetRows('*', $this->config->getUriTable(), 'page_uid=' . intval($page) . ' AND sys_language_uid=' . intval($language) . ' AND domain=' . $this->db->fullQuoteStr($domain, $this->config->getUriTable()) . ' AND hash_params="' . md5(tx_naworkuri_helper::implode_parameters($params)) . '" AND deleted=0 AND type=0', '', '', 1);
		if (is_array($urls) && count($urls) > 0) {
			return $urls[0];
		}
		return FALSE;
	}

	public function findOldUrl($domain, $path) {
		$urls = $this->db->exec_SELECTgetRows('*', $this->config->getUriTable(), 'domain=' . $this->db->fullQuoteStr($domain, $this->config->getUriTable()) . ' AND hash_path="' . md5($path) . '" AND deleted=0 AND type=1', '', '', 1);
		if (is_array($urls) && count($urls) > 0) {
			return $urls[0];
		}
		return FALSE;
	}

	/**
	 *
	 * @param array $parameters
	 * @param string $domain
	 * @param integer $language
	 * @param string $path
	 * @return string
	 */
	public function writeUrl($parameters, $domain, $language, $path) {
		$pageUid = intval($parameters['id']);
		$language = intval($language ? $language : 0);
		unset($parameters['id']);
		unset($parameters['L']);
		/* try to find an existing url that was too old to be retreived from cache */
		$existingUrl = $this->findExistantUrl($pageUid, $language, $parameters, $domain);
		if ($existingUrl !== FALSE) {
			$this->updateUrl($existingUrl['uid'], $pageUid, $language, $parameters);
			return $existingUrl['path'];
		}
		/* try find an old url that could be reactivated */
		$existingUrl = $this->findOldUrl($domain, $path);
		if ($existingUrl != FALSE) {
			$this->makeOldUrl($domain, $pageUid, $language, $parameters, $existingUrl['uid']);
			$this->updateUrl($existingUrl['uid'], $pageUid, $language, $parameters);
			return $path;
		}
		/* if we also did not find a url here we must create it */
		$this->makeOldUrl($domain, $pageId, $language, $parameters);
		$path = $this->unique($path, $domain);
		$this->createUrl($pageUid, $language, $domain, $parameters, $path);
		return $path;
	}

	/**
	 * Read Cache entry for the given URI
	 *
	 * @param string $path URI Path
	 * @param string $domain Current Domain
	 * @return array cache result
	 */
	public function read_path($path, $domain) {
		$hash_path = md5($path);
		$displayPageCondition = ' AND p.hidden=0 AND p.starttime < ' . time() . ' AND (p.endtime=0 OR p.endtime > ' . time() . ') ';
		if (tx_naworkuri_helper::isActiveBeUserSession()) {
			$displayPageCondition = '';
		}
		$uris = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'u.*', $this->config->getUriTable() . ' u, ' . $this->config->getPageTable() . ' p', 'u.deleted=0 AND u.hash_path="' . $hash_path . '" AND u.domain="' . $domain . '" ' . $displayPageCondition . ' AND p.deleted=0 AND (p.uid=u.page_uid OR u.type=2)'
		);

		if (is_array($uris) && count($uris) > 0) {
			return $uris[0];
		}
		return false;
	}

	/**
	 *
	 * @param integer $page
	 * @param integer $language
	 * @param string $domain
	 * @param array $parameters
	 * @param string $path
	 */
	private function createUrl($page, $language, $domain, $parameters, $path) {
		$parameters = tx_naworkuri_helper::implode_parameters($parameters);
		$this->db->exec_INSERTquery($this->config->getUriTable(), array(
			'pid' => $this->config->getStoragePage(),
			'page_uid' => intval($page),
			'tstamp' => time(),
			'crdate' => time(),
			'sys_language_uid' => intval($language),
			'domain' => $domain,
			'path' => $path,
			'hash_path' => md5($path),
			'params' => $parameters,
			'hash_params' => md5($parameters)
				), array(
			'pid',
			'page_uid',
			'tstamp',
			'crdate',
			'sys_language_uid'
		));
	}

	/**
	 * Update an url record based on the uid and domain with the new page, language, parameters and type
	 *
	 * @param int $uid
	 * @param string $domain
	 * @param int $page
	 * @param int $language
	 * @param array $parameters
	 * @param int $type
	 */
	private function updateUrl($uid, $page, $language, $parameters, $type = self::TX_NAWORKURI_URI_TYPE_NORMAL) {
		$parameters = tx_naworkuri_helper::implode_parameters($parameters);
		$this->db->exec_UPDATEquery($this->config->getUriTable(), 'uid=' . intval($uid), array(
			'page_uid' => intval($page),
			'sys_language_uid' => $language,
			'params' => $parameters,
			'hash_params' => md5($parameters),
			'type' => intval($type),
			'tstamp' => time()
				), array(
			'page_uid',
			'sys_language_uid',
			'type',
			'tstamp'
		));
	}

	/**
	 * Creates old url for the given page,language and paramters, the should not but might be more than one
	 *
	 * @param int $pageId
	 * @param int $language
	 * @param string $parameters
	 */
	private function makeOldUrl($domain, $pageId, $language, $parameters, $excludeUid = FALSE) {
		$this->db->exec_UPDATEquery($this->config->getUriTable(), 'domain=' . $this->db->fullQuoteStr($domain, $this->config->getUriTable()) . ' AND hash_params=' . $this->db->fullQuoteStr(md5($parameters), $this->config->getUriTable()) . ' AND page_uid=' . intval($pageId) . ' AND sys_language_uid=' . intval($language) . ($excludeUid !== FALSE ? ' AND uid!=' . intval($excludeUid) : '') . ' AND type=0', array(
			'type' => self::TX_NAWORKURI_URI_TYPE_OLD,
			'tstamp' => time()
				), array(
			'type',
			'tstamp')
		);
	}

	/**
	 * Make shure this URI is unique for the current domain
	 *
	 * @param string $uri URI
	 * @return string unique URI
	 */
	public function unique($uri, $domain, $uid=0) {
		$uriAppend = $this->config->getAppend();
		$baseUri = '';
		/*
		 * if the append is at the end of the uri then remove it for adding the unique part
		 */
		if (!empty($uriAppend) && substr($uri, -strlen($uriAppend)) == $uriAppend) {
			$baseUri = substr($uri, 0, strlen($uri) - strlen($uriAppend));
		} else {
			$baseUri = $uri;
		}
		$tmp_uri = $uri;
		$search_hash = md5($tmp_uri);
		$search_domain = $domain;
		$additionalWhere = '';
		if (!empty($domain)) {
			$additionalWhere .= ' AND domain LIKE \'' . $domain . '\'';
		}
		if ($uid > 0) {
			$additionalWhere .= ' AND uid!=' . intval($uid);
		}

		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', $this->config->getUriTable(), 'deleted=0 AND hash_path = "' . $search_hash . '"' . $additionalWhere);

		if ($dbres > 0) {
			// make the uri unique
			$append = 0;
			do {
				$append++;
				if (!empty($baseUri)) {
					$tmp_uri = $baseUri . '-' . $append . $uriAppend; // add the unique part and the uri append part to the base uri
				} else {
					$tmp_uri = $append . $uriAppend;
				}
				$search_hash = md5($tmp_uri);
				$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', $this->config->getUriTable(), 'deleted=0 AND hash_path = "' . $search_hash . '"' . $additionalWhere);
			} while ($dbres > 0);
		}
		return $tmp_uri;
	}

}

?>
