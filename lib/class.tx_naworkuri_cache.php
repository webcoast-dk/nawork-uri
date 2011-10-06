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
	private $timeout = false;

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
		$this->setTimeout(10); // set timeout to 10 seconds by default to avoid re creating while generating a page
		$this->db = $GLOBALS['TYPO3_DB'];
	}

	/**
	 * set Timeout for cache
	 * @param int $to
	 * @return unknown_type
	 */
	public function setTimeout($to) {
//		$this->timeout = $to;
		$this->timeout = 1;
	}

	/*
	 * Read a previously created URI from cache
	 *
	 * @param array $params Parameter Array
	 * @param string $domain current Domain
	 * @return string URI if found otherwise false
	 */

	public function read_params($params, $domain, $ignoreTimeout = TRUE, $allowRedirects = TRUE) {
		$uid = (int) $params['id'];
		$lang = (int) ($params['L'] ? $params['L'] : 0);

		unset($params['id']);
		unset($params['L']);

		$imploded_params = $this->helper->implode_parameters($params);

		$urls = $this->read($lang, $domain, $imploded_params, $ignoreTimeout, $allowRedirects);
		if (is_array($urls)) {
			foreach ($urls as $u) {
				if ($u['type'] == self::TX_NAWORKURI_URI_TYPE_NORMAL && $u['page_uid'] == $uid) {
					return $u['path'];
					break;
				}
			}
			return FALSE;
		} else {
			return FALSE;
		}
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
		if(tx_naworkuri_helper::isActiveBeUserSession()) {
			$displayPageCondition = '';
		}
		$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = 1;
		$uris = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'u.*', $this->config->getUriTable() . ' u, ' . $this->config->getPageTable() . ' p',
				'u.deleted=0 AND u.hash_path="' . $hash_path . '" AND u.domain="' . $domain . '" ' . $displayPageCondition .' AND p.deleted=0 AND (p.uid=u.page_uid OR u.type=2)'
		);

		if (is_array($uris) && count($uris) > 0) {
			return $uris[0];
		}
		return false;
	}

	/**
	 * Find the cached URI for the given parameters
	 *
	 * @param int $id        : the if param
	 * @param int $lang      : the L param
	 * @param string $domain : the current domain '' for not multidomain setups
	 * @param array $params  : other  url parameters
	 * @return string        : uri wich matches to these params otherwise false
	 */
	public function read($lang, $domain, $parameters, $ignoreTimeout = FALSE, $allowRedirects = TRUE) {
		$timeout_condition = '';
		if ($this->timeout > 0 && $ignoreTimeout == false) {
			$timeout_condition = 'AND ( tstamp > "' . (time() - $this->timeout) . '" OR locked=1 )';
		}
		$redirectCondition = '';
		if (!$allowRedirects) {
			$redirectCondition = ' AND type=0';
		}

		// lookup in db
		$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = 1;
		$urls = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'*', $this->config->getUriTable(), 'type < 2 AND deleted=0 AND sys_language_uid=' . $lang . ' AND domain="' . $domain . '" AND hash_params = "' . md5($parameters) . '" ' . $timeout_condition . $redirectCondition, '', 'locked DESC'
		);
		return (is_array($urls) ? $urls : FALSE);
	}

	/**
	 * Write a new URI to cache
	 *
	 * @param array $params Parameter Array
	 * @param string $domain current Domain
	 * @param string $path preferred Path
	 * @param string $debug_info Debug Infos
	 * @return string URI wich was stored for the params
	 */
	public function write_params($params, $language, $domain, $path, $debug_info='') {
		$uid = intval($params['id']);
		$lang = intval($language ? $language : 0);

		unset($params['id']);
		unset($params['L']);

		$imploded_params = tx_naworkuri_helper::implode_parameters($params);
		return $this->write($uid, $lang, $domain, $imploded_params, $path, $debug_info);
	}

	/**
	 * Write a new URI-Parameter combination to the cache
	 *
	 * @param int $id id Parameter
	 * @param int $lang L Parameter
	 * @param string $domain current Domain
	 * @param string $parameters URI Paramters
	 * @param string $path Preferred URI Path
	 * @param string $debug_info Debig Informations
	 * @return string URI wich was stored for the params
	 */
	public function write($id, $lang, $domain, $parameters, $path, $debug_info = '') {
		/*
		 *  check for a uri existing url record
		 */
		$cachedUrls = $this->read($lang, $domain, $parameters, true);
		if (is_array($cachedUrls) && count($cachedUrls) > 0) {
			foreach ($cachedUrls as $url) {
				/*
				 * if the path is equal we must check if we have a redirect here and have to update it to use it as a normal url again
				 * the existing urls on this page, domain, language and parameter set will be updated to be redirects
				 * if the url is locked we also have to return the path directly
				 */
				if ($url['path'] == $path && $url['type'] == self::TX_NAWORKURI_URI_TYPE_OLD) {
					/* update the redirect to be used as a normal url again */
					$this->updateUrl($url['uid'], $domain, $id, $lang, $parameters, self::TX_NAWORKURI_URI_TYPE_NORMAL);
					/* make old urls by page, language and parameters, they will automatically redirect to the new url updated above */
					$this->makeOldUrl($domain, $id, $lang, $parameters, $url['uid']);
					return $url['path'];
				} elseif ($url['locked'] == 1 && $url['page_uid'] == $id) {
					/* if the url is locked and the page_uid is correct return the cached path */
					return $url['path'];
				} elseif ($url['path'] == $path && $url['page_uid'] == $id) {
					/* if the path is equal and the page_uid is also correct return the cached path */
					return $url['path'];
				}
			}
			/*
			 * we haven't found any url we can reactivate, so make the current
			 * one a redirect and create a new one
			 */
			reset($cachedUrls);
			foreach ($cachedUrls as $url) {
				/* if the path differs check for creating a redirect */
				if ($url['page_uid'] == $id && $url['type'] == self::TX_NAWORKURI_URI_TYPE_NORMAL) {
					/* if the path differs but the params are equals make a redirect */
					if ($url['hash_params'] == md5($parameters)) {
						$this->changeType($url['uid'], self::TX_NAWORKURI_URI_TYPE_OLD);
					}
					/*
					 * Make the path unique, including the redirect created above in the check.
					 * This situation should not be possible because if the path of the url is the
					 * same as the given one the path should be returned, but to be sure we check it here
					 */
					$path = $this->unique($path, $domain);
					/* Well, if we have a unique path create the url */
					$this->createUrl($id, $lang, $domain, $parameters, $path);
					return $path;
				}
			}
		}
		/*
		 * if no path is returned proceed with normal creation
		 */
		$path = $this->unique($path, $domain);
		$this->createUrl($id, $lang, $domain, $parameters, $path);
		return $path;
	}

	/**
	 * Change the type of the url
	 *
	 * @param int $uid
	 * @param int $type
	 */
	private function changeType($uid, $type = self::TX_NAWORKURI_URI_TYPE_NORMAL) {
		$this->db->exec_UPDATEquery($this->config->getUriTable(), 'uid=' . intval($uid), array('type' => intval($type), 'tstamp' => time()), array('type', 'tstamp'));
	}

	private function createUrl($page, $language, $domain, $parameters, $path) {
		$this->db->exec_INSERTquery($this->config->getUriTable(), array(
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
	 * @param string $parameters
	 * @param int $type
	 */
	private function updateUrl($uid, $domain, $page, $language, $parameters, $type) {
		$this->db->exec_UPDATEquery($this->config->getUriTable(), 'uid=' . intval($uid) . ' AND domain=' . $this->db->fullQuoteStr($domain, $this->config->getUriTable()), array(
			'page_uid' => intval($page),
			'sys_language_uid' => $language,
			'params' => $parameters,
			'hash_params' => md5($parameters),
			'type' => intval($type)
				), array(
			'page_uid',
			'sys_language_uid',
			'type'
		));
	}

	/**
	 * Creates old url for the given page,language and paramters, the should not but might be more than one
	 *
	 * @param int $pageId
	 * @param int $language
	 * @param string $parameters
	 */
	private function makeOldUrl($domain, $pageId, $language, $parameters, $excludeUid) {
		$this->db->exec_UPDATEquery($this->config->getUriTable(), 'domain=' . $this->db->fullQuoteStr($domain, $this->config->getUriTable()) . ' AND hash_params=' . $this->db->fullQuoteStr(md5($parameters), $this->config->getUriTable()) . ' AND page_uid=' . intval($pageId) . ' AND sys_language_uid=' . intval($language) . ' AND uid!=' . intval($excludeUid) . ' AND type=0', array(
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

		$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = 1;
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
