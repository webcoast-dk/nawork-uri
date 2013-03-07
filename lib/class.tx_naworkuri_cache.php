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
		$this->db->store_lastBuiltQuery = 0;
	}

	/**
	 * Set the timeout for the url cache validity
	 *
	 * @param int $time Number of seconds the url should be valid, defaults to 86400 (= one day)
	 */
	public function setTimeout($time = 86400) {
		$this->timeout = $time;
	}

	/**
	 * Find a url based on the parameters and the domain
	 *
	 * @param array $params
	 * @param string $domain
	 */
	public function findCachedUrl($params, $domain, $language) {
		$uid = (int) $params['id'];
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
		/* if there is no be user logged in, hidden or time controlled non visible pages should not return a url */
		$displayPageCondition = ' AND p.hidden=0 AND p.starttime < ' . time() . ' AND (p.endtime=0 OR p.endtime > ' . time() . ') ';
		if (tx_naworkuri_helper::isActiveBeUserSession()) {
			$displayPageCondition = '';
		}
		$domainCondition = '';
		if ($this->config->isMultiDomainEnabled()) {
			$domainCondition = ' AND u.domain=' . $this->db->fullQuoteStr($domain, $this->config->getUriTable());
		}
		$urls = $this->db->exec_SELECTgetRows(
			'u.*', $this->config->getUriTable() . ' u, ' . $this->config->getPageTable() . ' p', 'u.page_uid=' . intval($uid) . ' AND sys_language_uid=' . intval($language) . ' AND hash_params="' . md5(tx_naworkuri_helper::implode_parameters($params, FALSE)) . '" ' . $domainCondition . $displayPageCondition . ' AND p.deleted=0 AND p.uid=u.page_uid AND type=0 AND ( u.tstamp > ' . (time() - $this->timeout) . ' OR locked=1 )', // lets find type 0 urls only from the cache
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
	public function findExistantUrl($page, $language, $params, $path, $domain) {
		$domainCondition = '';
		if ($this->config->isMultiDomainEnabled()) {
			$domainCondition = ' AND domain=' . $this->db->fullQuoteStr($domain, $this->config->getUriTable());
		}
		$urls = $this->db->exec_SELECTgetRows('*', $this->config->getUriTable(), 'page_uid=' . intval($page) . ' AND sys_language_uid=' . intval($language) . $domainCondition . ' AND hash_params="' . md5(tx_naworkuri_helper::implode_parameters($params, FALSE)) . '" AND hash_path="' . md5($path) . '" AND type=0', '', '', 1);
		if (is_array($urls) && count($urls) > 0) {
			return $urls[0];
		}
		return FALSE;
	}

	/**
	 * Find an old url based on the domain and path. It will be reused with new parameters.
	 * If no old url is found, this function looks for a url on a hidden or deleted page.
	 *
	 * @param string $domain
	 * @param string $path
	 * @return type
	 */
	public function findOldUrl($domain, $path) {
		$domainCondition = '';
		if ($this->config->isMultiDomainEnabled()) {
			$domainCondition = ' AND domain=' . $this->db->fullQuoteStr($domain, $this->config->getUriTable());
		}
		$urls = $this->db->exec_SELECTgetRows('*', $this->config->getUriTable(), 'hash_path="' . md5($path) . '" AND type=1' . $domainCondition, '', '', 1);
		if (is_array($urls) && count($urls) > 0) {
			return $urls[0];
		}
		/* try to find a url on a deleted or hidden page */
		$urls = $this->db->exec_SELECTgetRows('u.*', $this->config->getUriTable() . ' u, ' . $this->config->getPageTable() . ' p', 'u.page_uid=p.uid AND (p.deleted=1 OR p.hidden=1) AND u.hash_path="' . md5($path) . '"' . $domainCondition, '', '', 1);
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
		$orginalParameters = $parameters;
		$pageUid = intval($parameters['id']);
		$language = intval($language ? $language : 0);
		unset($parameters['id']);
		unset($parameters['L']);
		/* try to find an existing url that was too old to be retreived from cache */
		$existingUrl = $this->findExistantUrl($pageUid, $language, $parameters, $path, $domain);
		if ($existingUrl !== FALSE) {
			$this->touchUrl($existingUrl['uid']);
			$this->makeOldUrl($domain, $pageUid, $language, $parameters, $existingUrl['uid']);
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
		$this->makeOldUrl($domain, $pageUid, $language, $parameters);
		$uniquePath = $this->unique($pageUid, $language, $path, $parameters, $domain); // make the url unique
		if ($uniquePath === FALSE) {
			throw new Tx_NaworkUri_Exception_UrlIsNotUniqueException($path, $domain, $orginalParameters, $language);
		}
		/* try to find an existing url that was too old to be retreived from cache */
		$existingUrl = $this->findExistantUrl($pageUid, $language, $parameters, $uniquePath, $domain);
		if ($existingUrl !== FALSE) {
			$this->touchUrl($existingUrl['uid']);
			$this->makeOldUrl($domain, $pageUid, $language, $parameters, $existingUrl['uid']);
			return $existingUrl['path'];
		}
		/* try find an old url that could be reactivated */
		$existingUrl = $this->findOldUrl($domain, $uniquePath);
		if ($existingUrl != FALSE) {
			$this->makeOldUrl($domain, $pageUid, $language, $parameters, $existingUrl['uid']);
			$this->updateUrl($existingUrl['uid'], $pageUid, $language, $parameters);
			return $path;
		}
		$this->createUrl($pageUid, $language, $domain, $parameters, $uniquePath, $path);
		return $uniquePath;
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
		$domainCondition = '';
		if ($this->config->isMultiDomainEnabled()) {
			$domainCondition = ' AND u.domain=' . $this->db->fullQuoteStr($domain, $this->config->getUriTable());
		}
		$uris = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'u.*', $this->config->getUriTable() . ' u, ' . $this->config->getPageTable() . ' p', 'u.hash_path="' . $hash_path . '"' . $domainCondition . $displayPageCondition . ' AND p.deleted=0 AND (p.uid=u.page_uid OR u.type=2)'
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
	public function createUrl($page, $language, $domain, $parameters, $path, $originalPath) {
		$parameters = tx_naworkuri_helper::implode_parameters($parameters, FALSE);
		$result = @$this->db->exec_INSERTquery($this->config->getUriTable(), array(
				'pid' => $this->config->getStoragePage(),
				'page_uid' => intval($page),
				'tstamp' => time(),
				'crdate' => time(),
				'sys_language_uid' => intval($language),
				'domain' => $domain,
				'path' => $path,
				'hash_path' => md5($path),
				'params' => $parameters,
				'hash_params' => md5($parameters),
				'original_path' => $originalPath
				), array(
				'pid',
				'page_uid',
				'tstamp',
				'crdate',
				'sys_language_uid'
			));
		if (!$result) {
			throw new Tx_NaworkUri_Exception_DbErrorException($this->db->sql_error());
		}
	}

	private function touchUrl($uid) {
		$this->db->exec_UPDATEquery($this->config->getUriTable(), 'uid=' . intval($uid), array('tstamp' => time()), array('tstamp'));
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
		$parameters = tx_naworkuri_helper::implode_parameters($parameters, FALSE);
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
	 * @param array $parameters
	 */
	private function makeOldUrl($domain, $pageId, $language, $parameters, $excludeUid = FALSE) {
		$domainConstraint = '';
		if (!empty($domain)) {
			$domainConstraint = ' AND domain=' . $this->db->fullQuoteStr($domain, $this->config->getUriTable());
		}
		$this->db->exec_UPDATEquery($this->config->getUriTable(), 'hash_params=' . $this->db->fullQuoteStr(md5(tx_naworkuri_helper::implode_parameters($parameters, FALSE)), $this->config->getUriTable()) . $domainConstraint . ' AND page_uid=' . intval($pageId) . ' AND sys_language_uid=' . intval($language) . ($excludeUid !== FALSE ? ' AND uid!=' . intval($excludeUid) : '') . ' AND type=0', array(
			'type' => self::TX_NAWORKURI_URI_TYPE_OLD,
			'tstamp' => time()
			), array(
			'type',
			'tstamp')
		);
	}

	/**
	 * Make sure this URI is unique for the current domain
	 *
	 * @param string $uri URI
	 * @return string unique URI
	 */
	public function unique($pageUid, $language, $path, $parameters, $domain) {
		$pathHash = md5($path);
		$parameterHash = md5(tx_naworkuri_helper::implode_parameters($parameters, FALSE));
		$additionalWhere = '';
		if (!empty($domain)) {
			$additionalWhere .= ' AND domain LIKE ' . $this->db->fullQuoteStr($domain, $this->config->getUriTable());
		}
		$dbRes = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', $this->config->getUriTable(), '(page_uid!=' . intval($pageUid) . ' OR sys_language_uid!=' . intval($language) . ' OR hash_params != ' . $this->db->fullQuoteStr($parameterHash, $this->config->getUriTable()) . ') AND hash_path = ' . $this->db->fullQuoteStr($pathHash, $this->config->getUriTable()) . $additionalWhere, '', '', '1');
		if (count($dbRes) > 0) {
			/* so we have to make the url unique */
			$cachedUrl = $this->db->exec_SELECTgetRows('*', $this->config->getUriTable(), 'page_uid=' . intval($pageUid) . ' AND sys_language_uid=' . intval($language) . ' AND type=0 AND hash_params=' . $this->db->fullQuoteStr($parameterHash, $this->config->getUriTable()) . $additionalWhere, '', '', '1');
			if (count($cachedUrl) > 0 && $cachedUrl[0]['original_path'] == $path) {
				/* there is a url found with the parameter set, so lets use this path */
				return $cachedUrl[0]['path'];
			}
			// make the uri unique
			$append = 0;
			$baseUri = substr($path, -(strlen($this->config->getAppend()))) == $this->config->getAppend() ? substr($path, 0, -strlen($this->config->getAppend())) : $path;
			$tmp_uri = $path;
			do {
				$append++;
				if ($append > 10) {
					return FALSE; // return false, to throw an exception in writeUrl function
				}
				if (!empty($baseUri)) {
					$tmp_uri = $baseUri . '-' . $append . $this->config->getAppend(); // add the unique part and the uri append part to the base uri
				} else {
					$tmp_uri = $append . $this->config->getAppend();
				}
				$search_hash = md5($tmp_uri);
				$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', $this->config->getUriTable(), '(page_uid!=' . intval($pageUid) . ' OR sys_language_uid!=' . intval($language) . ' OR hash_params != ' . $this->db->fullQuoteStr($parameterHash, $this->config->getUriTable()) . ') AND hash_path=' . $this->db->fullQuoteStr($search_hash, $this->config->getUriTable()) . $additionalWhere, '', '', '1');
			} while (count($dbres) > 0);
			return $tmp_uri;
		}
		return $path;
	}

}

?>
