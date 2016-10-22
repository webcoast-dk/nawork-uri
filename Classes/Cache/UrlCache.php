<?php

namespace Nawork\NaworkUri\Cache;

use Nawork\NaworkUri\Configuration\TableConfiguration;
use Nawork\NaworkUri\Exception\DbErrorException;
use Nawork\NaworkUri\Exception\UrlIsNotUniqueException;
use Nawork\NaworkUri\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UrlCache {

	const URI_TYPE_NORMAL = 0;
	const URI_TYPE_OLD = 1;
	const URI_TYPE_REDIRECT = 2;

	private $timeout = 86400;

	/**
	 *
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	private $db = NULL;

	/**
	 *
	 * @var \Nawork\NaworkUri\Configuration\TableConfiguration
	 */
	private $tableConfiguration;

	/**
	 * Constructor
	 *
	 */
	public function __construct() {
		$this->db = $GLOBALS['TYPO3_DB'];
		$this->db->store_lastBuiltQuery = 1;
		$this->tableConfiguration = GeneralUtility::makeInstance(TableConfiguration::class);
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
	 * @param array  $params
	 * @param string $domain
	 * @param integer $language
	 * @param boolean $ignoreTimeout
	 *
	 * @return boolean|array
	 */
	public function findCachedUrl($params, $domain, $language, $ignoreTimeout) {
		$uid = (int) $params['id'];
		unset($params['id']);
		unset($params['L']);
		/* evaluate the cache timeout */
		$pageRes = $this->db->exec_SELECTgetRows('*', $this->tableConfiguration->getPageTable(), 'uid=' . intval($uid));
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
		if (\Nawork\NaworkUri\Utility\GeneralUtility::isActiveBeUserSession()) {
			$displayPageCondition = '';
		}
		$timeoutConstraint = '';
		if(!$ignoreTimeout) {
			$timeoutConstraint = ' AND ( u.tstamp > ' . (time() - $this->timeout) . ' OR locked=1 )';
		}
		$domainCondition = ' AND u.domain=' . (int) $domain;
		$urls = $this->db->exec_SELECTgetRows('u.*', $this->tableConfiguration->getUrlTable() . ' u, ' . $this->tableConfiguration->getPageTable() . ' p', 'u.page_uid=' . intval($uid) . ' AND sys_language_uid=' . intval($language) . ' AND parameters_hash="' . md5(\Nawork\NaworkUri\Utility\GeneralUtility::implode_parameters($params, FALSE)) . '" ' . $domainCondition . $displayPageCondition . ' AND p.deleted=0 AND p.uid=u.page_uid AND type=0' . $timeoutConstraint, // lets find type 0 urls only from the cache
			'', '', '1');
		if (is_array($urls) && count($urls) > 0) {
			return $urls[0]['path'];
		}

		return FALSE;
	}

	/**
	 * Find an existing url based on the page's id, language, parameters and domain.
	 * This is used to get an url that's cache time has expired but is a normal url.
	 *
	 * @param integer    $page
	 * @param integer    $language
	 * @param array      $params
	 * @param string     $path
	 * @param int|string $domain
	 *
	 * @return array|boolean
	 */
	public function findExistantUrl($page, $language, $params, $path, $domain) {
		$domainCondition = ' AND domain=' . (int) $domain;
		$urls = $this->db->exec_SELECTgetRows('*', $this->tableConfiguration->getUrlTable(), 'page_uid=' . intval($page) . ' AND sys_language_uid=' . intval($language) . $domainCondition . ' AND parameters_hash="' . md5(\Nawork\NaworkUri\Utility\GeneralUtility::implode_parameters($params, FALSE)) . '" AND path_hash="' . md5($path) . '" AND type=0', '', '', 1);
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
	 *
	 * @return array|boolean
	 */
	public function findOldUrl($domain, $path) {
		$domainCondition = ' AND domain=' . (int) $domain;
		$urls = $this->db->exec_SELECTgetRows('*', $this->tableConfiguration->getUrlTable(), 'path_hash="' . md5($path) . '" AND type=1' . $domainCondition, '', '', 1);
		if (is_array($urls) && count($urls) > 0) {
			return $urls[0];
		}
		/* try to find a url on a deleted or hidden page */
		$urls = $this->db->exec_SELECTgetRows('u.*', $this->tableConfiguration->getUrlTable() . ' u, ' . $this->tableConfiguration->getPageTable() . ' p', 'u.page_uid=p.uid AND (p.deleted=1 OR p.hidden=1) AND u.path_hash="' . md5($path) . '"' . $domainCondition, '', '', 1);
		if (is_array($urls) && count($urls) > 0) {
			return $urls[0];
		}

		return FALSE;
	}

	/**
	 *
	 * @param array   $parameters
	 * @param string  $domain
	 * @param integer $language
	 * @param string  $path
	 *
	 * @return string
	 * @throws UrlIsNotUniqueException
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
			throw new UrlIsNotUniqueException($path, $domain, $orginalParameters, $language);
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

			return $uniquePath;
		}
		$this->createUrl($pageUid, $language, $domain, $parameters, $uniquePath, $path);

		return $uniquePath;
	}

	/**
	 * Read Cache entry for the given URI
	 *
	 * @param string $path   URI Path
	 * @param string $domain Current Domain
	 *
	 * @return array|boolean cache result
	 */
	public function read_path($path, $domain) {
		$path_hash = md5($path);
		$displayPageCondition = ' AND p.hidden=0 AND p.starttime < ' . time() . ' AND (p.endtime=0 OR p.endtime > ' . time() . ') ';
		if (\Nawork\NaworkUri\Utility\GeneralUtility::isActiveBeUserSession()) {
			$displayPageCondition = '';
		}
		$domainCondition = ' AND u.domain=' . (int) $domain;
		$uris = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('u.*', $this->tableConfiguration->getUrlTable() . ' u, ' . $this->tableConfiguration->getPageTable() . ' p', 'u.path_hash="' . $path_hash . '"' . $domainCondition . $displayPageCondition . ' AND p.deleted=0 AND (p.uid=u.page_uid OR u.type=2)', '', '', '1');

		if (is_array($uris) && count($uris) > 0) {
			return $uris[0];
		}

		return FALSE;
	}

	/**
	 *
	 * @param integer $page
	 * @param integer $language
	 * @param string  $domain
	 * @param array   $parameters
	 * @param string  $path
	 * @param string  $originalPath
	 *
	 * @throws DbErrorException
	 */
	public function createUrl($page, $language, $domain, $parameters, $path, $originalPath) {
		$parameters = \Nawork\NaworkUri\Utility\GeneralUtility::implode_parameters($parameters, FALSE);
		/* disable debug output from the database layer */
		$this->db->debugOutput = FALSE;
		$result = $this->db->exec_INSERTquery($this->tableConfiguration->getUrlTable(), array('page_uid' => intval($page), 'tstamp' => time(), 'crdate' => time(), 'sys_language_uid' => intval($language), 'domain' => $domain, 'path' => $path, 'path_hash' => md5($path), 'parameters' => $parameters, 'parameters_hash' => md5($parameters), 'original_path' => $originalPath), array('page_uid', 'tstamp', 'crdate', 'sys_language_uid'));

		if (!$result) {
			throw new DbErrorException($this->db->sql_error());
		}
	}

	private function touchUrl($uid) {
		$this->db->exec_UPDATEquery($this->tableConfiguration->getUrlTable(), 'uid=' . intval($uid), array('tstamp' => time()), array('tstamp'));
	}

	/**
	 * Update an url record based on the uid and domain with the new page, language, parameters and type
	 *
	 * @param int    $uid
	 * @param int    $page
	 * @param int    $language
	 * @param array  $parameters
	 * @param int    $type
	 */
	private function updateUrl($uid, $page, $language, $parameters, $type = self::URI_TYPE_NORMAL) {
		$parameters = \Nawork\NaworkUri\Utility\GeneralUtility::implode_parameters($parameters, FALSE);
		$this->db->exec_UPDATEquery($this->tableConfiguration->getUrlTable(), 'uid=' . intval($uid), array('page_uid' => intval($page), 'sys_language_uid' => $language, 'parameters' => $parameters, 'parameters_hash' => md5($parameters), 'type' => intval($type), 'tstamp' => time()), array('page_uid', 'sys_language_uid', 'type', 'tstamp'));
	}

	/**
	 * Creates old url for the given page,language and paramters, the should not but might be more than one
	 *
	 * @param int         $domain
	 * @param int         $pageId
	 * @param int         $language
	 * @param array       $parameters
	 * @param int|boolean $excludeUid
	 */
	private function makeOldUrl($domain, $pageId, $language, $parameters, $excludeUid = FALSE) {
		$domainCondition = ' AND domain=' . (int) $domain;
		$this->db->exec_UPDATEquery($this->tableConfiguration->getUrlTable(), 'parameters_hash=' . $this->db->fullQuoteStr(md5(\Nawork\NaworkUri\Utility\GeneralUtility::implode_parameters($parameters, FALSE)), $this->tableConfiguration->getUrlTable()) . $domainCondition . ' AND page_uid=' . intval($pageId) . ' AND sys_language_uid=' . intval($language) . ($excludeUid !== FALSE ? ' AND uid!=' . intval($excludeUid) : '') . ' AND type=0', array('type' => self::URI_TYPE_OLD, 'tstamp' => time()), array('type', 'tstamp'));
	}

	/**
	 * Make sure this URI is unique for the current domain
	 *
	 * @param int    $pageUid
	 * @param int    $language
	 * @param string $path
	 * @param array  $parameters
	 * @param int    $domain
	 *
	 * @return string unique URI
	 */
	public function unique($pageUid, $language, $path, $parameters, $domain) {
		$pathHash = md5($path);
		$parameterHash = md5(\Nawork\NaworkUri\Utility\GeneralUtility::implode_parameters($parameters, FALSE));
		$additionalWhere = ' AND domain=' . (int) $domain;
		$dbRes = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', $this->tableConfiguration->getUrlTable(), '(page_uid!=' . intval($pageUid) . ' OR sys_language_uid!=' . intval($language) . ' OR parameters_hash != ' . $this->db->fullQuoteStr($parameterHash, $this->tableConfiguration->getUrlTable()) . ') AND path_hash = ' . $this->db->fullQuoteStr($pathHash, $this->tableConfiguration->getUrlTable()) . $additionalWhere, '', '', '1');
		if (count($dbRes) > 0) {
			/* so we have to make the url unique */
			$cachedUrl = $this->db->exec_SELECTgetRows('*', $this->tableConfiguration->getUrlTable(), 'page_uid=' . intval($pageUid) . ' AND sys_language_uid=' . intval($language) . ' AND type=0 AND parameters_hash=' . $this->db->fullQuoteStr($parameterHash, $this->tableConfiguration->getUrlTable()) . $additionalWhere, '', '', '1');
			if (count($cachedUrl) > 0 && $cachedUrl[0]['original_path'] == $path) {
				/* there is a url found with the parameter set, so lets use this path */
				return $cachedUrl[0]['path'];
			}
			// make the uri unique
			$appendIteration = 0;
			$appendValue = ConfigurationUtility::getConfiguration()->getGeneralConfiguration()->getAppend();
			$baseUri = substr($path, -(strlen($appendValue))) == $appendValue ? substr($path, 0, -strlen($appendValue)) : $path;
			do {
				++$appendIteration;
				if ($appendIteration > 10) {
					return FALSE; // return false, to throw an exception in writeUrl function
				}
				if (!empty($baseUri)) {
					$tmp_uri = $baseUri . '-' . $appendIteration . $appendValue; // add the unique part and the uri append part to the base uri
				} else {
					$tmp_uri = $appendIteration . $appendValue;
				}
				$search_hash = md5($tmp_uri);
				$dbRes = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', $this->tableConfiguration->getUrlTable(), '(page_uid!=' . intval($pageUid) . ' OR sys_language_uid!=' . intval($language) . ' OR parameters_hash != ' . $this->db->fullQuoteStr($parameterHash, $this->tableConfiguration->getUrlTable()) . ') AND path_hash=' . $this->db->fullQuoteStr($search_hash, $this->tableConfiguration->getUrlTable()) . $additionalWhere, '', '', '1');
			} while (count($dbRes) > 0);

			return $tmp_uri;
		}

		return $path;
	}

}
