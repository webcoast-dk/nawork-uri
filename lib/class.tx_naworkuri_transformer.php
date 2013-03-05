<?php

require_once (PATH_t3lib . 'class.t3lib_page.php');

require_once (t3lib_extMgm::extPath('nawork_uri') . '/lib/class.tx_naworkuri_cache.php');
require_once (t3lib_extMgm::extPath('nawork_uri') . '/lib/class.tx_naworkuri_helper.php');

/**
 * Class for creating path uris
 *
 * @author Martin Ficzel
 *
 */
class tx_naworkuri_transformer implements t3lib_Singleton {

	/**
	 *
	 * @var tx_naworkuri_configReader
	 */
	private $config;

	/**
	 *
	 * @var string
	 */
	private $domain;

	/**
	 *
	 * @var t3lib_db
	 */
	private $db;

	/**
	 * @var tx_naworkuri_cache
	 */
	private $cache;

	/**
	 *
	 * @var integer
	 */
	private $language = 0;

	/**
	 * Constructor
	 *
	 * @param tx_naworkuri_configReader $config
	 * @param boolean $multidomain
	 * @param string $domain
	 */
	public function __construct($config, $multidomain = false, $domain = '') {
		$this->db = $GLOBALS['TYPO3_DB'];
		// read configuration
		$this->config = $config;
		// get the domain, if multiple domain is not enabled the helper return ""
		$this->domain = $domain;
		if (empty($this->domain)) {
			$this->domain = tx_naworkuri_helper::getCurrentDomain();
		}
		$this->cache = t3lib_div::makeInstance('tx_naworkuri_cache', $this->config);
		$this->cache->setTimeout(30);

		$this->helper = t3lib_div::makeInstance('tx_naworkuri_helper');
	}

	/**
	 * Convert the uri path to the request parameters
	 *
	 * @param string $uri
	 * @return array Parameters extracted from path and GET
	 */
	public function uri2params($uri = '') {
		// remove opening slash
		if (empty($uri))
			return;
		$append = (string) $this->config->getAppend();
		list($path, $params) = t3lib_div::trimExplode('?', $uri);
		// save the original path to check that if the "slashed" one does not return anything
		$orgPath = '';
		$path = urldecode($path);
		if (!empty($path) && $append == '/' && substr($path, -strlen($append)) != $append && !preg_match('/\.\w{3,5}\d?$/', $path)) {
			$orgPath = $path;
			$path .= (string) $this->config->getAppend();
		}

		// look into the db
		$cache = $this->cache->read_path($path, $this->domain);
		if ($cache === FALSE && !empty($orgPath)) { // if we don't get a url try with the original path if it is not empty
			$cache = $this->cache->read_path($orgPath, $this->domain);
		}
		if ($cache['type'] > 0) {
			throw new Tx_NaworkUri_Exception_UrlIsRedirectException($cache);
		}
		if ($cache) {
			// cachedparams
			$cachedparams = Array();
			parse_str($cache['params'], $cachedparams);
			$cachedparams['id'] = $cache['page_uid'];
			$cachedparams['L'] = $cache['sys_language_uid'];
			// classic url params
			$getparams = Array();
			parse_str($params, $getparams);
			// merged result
			$res = t3lib_div::array_merge_recursive_overrule($cachedparams, $getparams);
			return $res;
		}
		return false;
	}

	/**
	 * Encode Parameters as URI-Path
	 *
	 * @param str $param_str Parameter string
	 * @return string $uri encoded uri
	 */
	public function params2uri($param_str, $dontCreateNewUrls = FALSE, $ignoreTimeout = FALSE) {
		global $TYPO3_CONF_VARS;

		list($parameters, $anchor) = explode('#', $param_str, 2);
		$params = tx_naworkuri_helper::explode_parameters($parameters);
		$orgParams = $params;
		/* add hook for processing the parameter set */
		if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['tx_naworkuri']['parameterSet-preProcess'])) {
			foreach ($TYPO3_CONF_VARS['SC_OPTIONS']['tx_naworkuri']['parameterSet-preProcess'] as $funcRef) {
				t3lib_div::callUserFunction($funcRef, $params, $this);
			}
		}
		/* if something destroys the params reset them */
		if (!is_array($params) || !array_key_exists('id', $params)) {
			$params = $orgParams;
		}

		/* we must have an integer id so lets look it up */
		$params['id'] = tx_naworkuri_helper::aliasToId($params['id']);

		/* check if type should be casted to int to avoid strange behavior when creating links */
		if ($this->config->getCastTypeToInt()) {
			$type = !empty($params['type']) ? $params['type'] : t3lib_div::_GP('type');
			if (!empty($type) && !tx_naworkuri_helper::canBeInterpretedAsInteger($type)) { // if type is not an int
				unset($params['type']); // remove type param to use systems default
			}
		}

		/* check if L should be casted to int to avoid strange behavior when creating links */
		if ($this->config->getCastLToInt()) {
			$L = !empty($params['L']) ? $params['L'] : t3lib_div::_GP('L');
			if (!empty($L) && !tx_naworkuri_helper::canBeInterpretedAsInteger($L)) { // if L is not an int
				unset($params['L']); // remove L param to use system default
			}
		}
		if (!isset($params['L'])) {
			/* append an empty string to make sure this value is a string when given to t3lib_div::calculateCHash */
			$params['L'] = '' . ($GLOBALS['TSFE']->sys_language_uid ? $GLOBALS['TSFE']->sys_language_uid : 0);
		}

		/* recalculate the cHash to avoid doublicated urls with different cHashes based on encoded or non-encoded parameters, e.g. from the crawler */
		if (isset($params['cHash'])) {
			$cHashParams = $params;
			unset($cHashParams['cHash']);
			ksort($cHashParams);
			$params['cHash'] = t3lib_div::calculateCHash($cHashParams);
		}

		$this->language = $params['L'];
//		debug($parameters);
		/* find cached urls with the given parameters from the current domain */
		list($encodableParameters, $unencodableParameters) = tx_naworkuri_helper::filterConfiguredParameters($params);
		$cachedUri = $this->cache->findCachedUrl($encodableParameters, $this->domain, $this->language);
		if ($cachedUri !== FALSE) {
			/* compute the unencoded parameters */
			if (count($unencodableParameters) > 0) {
				$cachedUri .= '?' . tx_naworkuri_helper::implode_parameters($unencodableParameters);
			}
			/* append the anchor if not empty */
			if ($anchor) {
				$cachedUri .= '#' . $anchor;
			}
			return $cachedUri;
		} elseif ($dontCreateNewUrls && $cache_uri === false) {
			return false;
		}

		// create new uri because no exact match was found in cache
		$original_params = $params;
		$encoded_params = array();
		$unencoded_params = $original_params;

		// transform the parameters to path segments
		$path = array();
		$path = array_merge($path, $this->params2uri_predefinedparts($original_params, $unencoded_params, $encoded_params));
		$path = array_merge($path, $this->params2uri_valuemaps($original_params, $unencoded_params, $encoded_params));
		$path = array_merge($path, $this->params2uri_uriparts($original_params, $unencoded_params, $encoded_params));
		$path = array_merge($path, $this->params2uri_pagepath($original_params, $unencoded_params, $encoded_params));

		// write cache entry with these uri an create cache entry if needed
		$debug_info = '';
		$debug_info .= "original_params  : " . $this->helper->implode_parameters($original_params) . chr(10);
		$debug_info .= "encoded_params   : " . $this->helper->implode_parameters($encoded_params) . chr(10);
		$debug_info .= "unencoded_params : " . $this->helper->implode_parameters($unencoded_params) . chr(10);
		/*
		 * if any parameter is not encoded and the cHash is encoded, remove it from the encoded parameters
		 * and put it into the unencoded parameters to avoid unnecessary uris
		 */
		if (count($unencoded_params) > 0 && isset($encoded_params['cHash'])) {
			$unencoded_params['cHash'] = $encoded_params['cHash'];
			unset($encoded_params['cHash']);
		}

		// order the params like configured
		$ordered_params = array();
		foreach ($this->config->getParamOrder() as $param) {
			$param_name = (string) $param;
			if (isset($path[$param_name]) && $segment = $path[$param_name]) {
				if ($segment)
					$ordered_params[] = $segment;
				unset($path[$param_name]);
			}
		}
		// add params with not specified order
		foreach ($path as $param => $path_segment) {
			if ($path_segment)
				$ordered_params[] = $path_segment;
		}

		// return
		if (count($ordered_params)) {
			$encoded_uri = implode('/', $ordered_params);
		} else {
			$encoded_uri = '';
		}

		$result_path = $this->helper->sanitize_uri($encoded_uri);

		// append
		if ($result_path) {
			$append = $this->config->getAppend();
			if (substr($result_path, -strlen($append)) != $append) {
				$result_path = $result_path . $append;
			}
		}
		$uri = $this->cache->writeUrl($encoded_params, $this->domain, $this->language, $result_path);

		// read not encoded parameters
		$i = 0;
		foreach ($unencoded_params as $key => $value) {
			$uri.= ( ($i > 0) ? '&' : '?' ) . $key . '=' . $value;
			$i++;
		}

		// append stored anchor
		if ($anchor) {
			$uri .= '#' . $anchor;
		}

		return($uri);
	}

	/**
	 * Encode the predifined parts
	 *
	 * @param array $original_params  : original Parameters
	 * @param array $unencoded_params : unencoded Parameters
	 * @param array $encoded_params   : encoded Parameters
	 * @return array : Path Elements for final URI
	 */
	public function params2uri_predefinedparts(&$original_params, &$unencoded_params, &$encoded_params) {

		$parts = array();
		foreach ($this->config->getPredefinedParts() as $part) {

			$param_name = (string) $part->parameter;
			if ($param_name && isset($unencoded_params[$param_name])) {
				$value = (string) $part->value;
				$key = (string) $part->attributes()->key;
				$regex = (string) $part->attributes()->regex;

				if (!$key) {
					if (!$value && $value !== '0') {
						$encoded_params[$param_name] = $unencoded_params[$param_name];
						unset($unencoded_params[$param_name]);
					} elseif ($unencoded_params[$param_name] == $value) {
						$encoded_params[$param_name] = $unencoded_params[$param_name];
						unset($unencoded_params[$param_name]);
					}
				} else {
					if ($value && $unencoded_params[$param_name] == $value) {
						$encoded_params[$param_name] = $unencoded_params[$param_name];
						unset($unencoded_params[$param_name]);
						$parts[$param_name] = trim($key);
					} else if (!$value) {
						if ($regex) {
							if (preg_match($regex, $unencoded_params[$param_name])) {
								$parts[$param_name] = preg_replace($regex, $key, $unencoded_params[$param_name]);
								$encoded_params[$param_name] = $unencoded_params[$param_name];
								unset($unencoded_params[$param_name]);
							}
						} else {
							$parts[$param_name] = str_replace('###', $unencoded_params[$param_name], trim($key));
							$encoded_params[$param_name] = $unencoded_params[$param_name];
							unset($unencoded_params[$param_name]);
						}
					}
				}
			}
		}
		return $parts;
	}

	/**
	 * Encode tha Valuemaps
	 *
	 * @param array $original_params  : original Parameters
	 * @param array $unencoded_params : unencoded Parameters
	 * @param array $encoded_params   : encoded Parameters
	 * @return array : Path Elements for final URI
	 */
	public function params2uri_valuemaps(&$original_params, &$unencoded_params, &$encoded_params) {
		$parts = array();
		foreach ($this->config->getValueMaps() as $valuemap) {
			$param_name = (string) $valuemap->parameter;
			if ($param_name && isset($unencoded_params[$param_name])) {
				foreach ($valuemap->value as $value) {
					if ((string) $value == $unencoded_params[$param_name]) {
						$key = (string) $value->attributes()->key;
						$remove = (string) $value->attributes()->remove;
						if (!$remove) {
							if ($key) {
								$parts[$param_name] = trim($key);
							}
							$encoded_params[$param_name] = $unencoded_params[$param_name];
							unset($unencoded_params[$param_name]);
						} else {
							unset($unencoded_params[$param_name]);
						}
					}
				}
			}
		}
		return $parts;
	}

	/**
	 * Encode the Uriparts
	 *
	 * @param array $original_params  : original Parameters
	 * @param array $unencoded_params : unencoded Parameters
	 * @param array $encoded_params   : encoded Parameters
	 * @return array : Path Elements for final URI
	 */
	public function params2uri_uriparts(&$original_params, &$unencoded_params, &$encoded_params) {
		$parts = array();
		foreach ($this->config->getUriParts() as $uripart) {
			$value = '';

			$param_name = (string) $uripart->parameter;
			if ($param_name && array_key_exists($param_name, $unencoded_params) && strlen($unencoded_params[$param_name]) > 0) {
				try {
					$value = Tx_NaworkUri_Cache_TransformationCache::getTransformation($param_name, $unencoded_params[$param_name], $this->language);
				} catch (Tx_NaworkUri_Exception_TransformationValueNotFoundException $ex) {


					$table = (string) $uripart->table;
					$field = (string) $uripart->field;
					$selectFields = '*';
					/* if select fields are set, use them instead of "*" */
					$configSelectFields = (string) $uripart->selectFields;
					if (!empty($configSelectFields)) {
						/* make sure we select uid and pid for the record, that is needed for the language overlay */
						$selectFields = implode(',', array_merge(array('uid', 'pid'), t3lib_div::trimExplode(',', (string) $uripart->selectFields)));
					}
					$fallback = (string) $uripart->fallback;
					$foreignTable = (string) $uripart->foreignTable;
					$mmTable = (string) $uripart->mmTable;
					$where = (string) $uripart->where;


					$matches = array();
					$fieldmap = array();
					$fieldpattern = $field;

					if (!preg_match_all('/\{(.*?)\}/', $field, $matches)) {
						// single fields access
						$fieldmap = array($field);
						$fieldpattern = '###0###';
					} else {
						// multi field access
						list($found, $fields) = $matches;
						for ($i = 0; $i < count($found); $i++) {
							$fieldmap[] = $fields[$i];
							$fieldpattern = str_replace($found[$i], '###' . $i . '###', $fieldpattern);
						}
					}

					// find fields
					$where_part = str_replace('###', $unencoded_params[$param_name], $where);

					if (!empty($where_part) && !empty($GLOBALS['TCA'][$table]['ctrl']['languageField'])) {
						$where_part .= ' AND (' . $GLOBALS['TCA'][$table]['ctrl']['languageField'] . '=0 OR ' . $GLOBALS['TCA'][$table]['ctrl']['languageField'] . '=-1)';
					}
					if (!empty($table)) {
						if (empty($selectFields) || empty($foreignTable) || empty($mmTable)) {
							$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(empty($selectFields) ? '*' : $selectFields, $table, $where_part, '', '', 1);
						} else {
							$dbres = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query($selectFields, $table, $mmTable, $foreignTable, $where_part, '', '', 1);
						}
						/*
						 * if the query for a record with sys_language_uid 0 returns nothing let's try it with the current language
						 * this must be added to avoid an empty uri part if e.g. a news record is only available in english
						 */
						if ($GLOBALS['TYPO3_DB']->sql_num_rows($dbres) < 1 && $this->language > 0) {
							// find fields
							$where_part = str_replace('###', $unencoded_params[$param_name], $where);
							if (!empty($where_part) && !empty($GLOBALS['TCA'][$table]['ctrl']['languageField'])) {
								$where_part .= ' AND ' . $GLOBALS['TCA'][$table]['ctrl']['languageField'] . '=' . $this->language;
							}
							if (empty($selectFields) || empty($foreignTable) || empty($mmTable)) {
								$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(empty($selectFields) ? '*' : $selectFields, $table, $where_part, '', '', 1);
							} else {
								$dbres = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query($selectFields, $table, $mmTable, $foreignTable, $where_part, '', '', 1);
							}
						}
						if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)) {
							if (!empty($GLOBALS['TCA'][$table]['ctrl']['languageField'])) {
								$row = $GLOBALS['TSFE']->sys_page->getRecordOverLay($table, $row, $this->language);
							}
							$value = $fieldpattern;
							foreach ($fieldmap as $map_key => $map_value) {
								$mapfields = explode('//', $map_value);
								foreach ($mapfields as $mapfield) {
									if ($row[$mapfield]) {
										$value = str_replace('###' . $map_key . '###', $row[$mapfield], $value);
										break;
									}
								}
							}
						}
						$value = trim($value);
						/* if we have not found a transformation for the current value */
						if (empty($value)) {
							$value = $unencoded_params[$param_name];
							if (!empty($fallback) && strpos($fallback, '###') !== FALSE) { // if we have a fallback set, and we have the marker "###" in it replace this with the original value and use it as an uri part
								$value = str_replace('###', $value, $fallback);
							}
						}
						Tx_NaworkUri_Cache_TransformationCache::setTransformation($param_name, $unencoded_params[$param_name], $value, $this->language);
					}
				}
				$encoded_params[$param_name] = $unencoded_params[$param_name];
				unset($unencoded_params[$param_name]);
				$parts[$param_name] = $value;
			}
		}
		return $parts;
	}

	/**
	 * Encode the Pagepath
	 *
	 * @param array $original_params  : original Parameters
	 * @param array $unencoded_params : unencoded Parameters
	 * @param array $encoded_params   : encoded Parameters
	 * @return array : Path Elements for final URI
	 */
	public function params2uri_pagepath(&$original_params, &$unencoded_params, &$encoded_params) {
		$parts = array();
		$path = '';
		if ($this->config->hasPagePathConfig() && $unencoded_params['id']) {

			// cast id to int and resolve aliases
			if ($unencoded_params['id']) {
				if (tx_naworkuri_helper::canBeInterpretedAsInteger($unencoded_params['id'])) {
					$unencoded_params['id'] = (int) $unencoded_params['id'];
				} else {
					$str = $GLOBALS['TYPO3_DB']->fullQuoteStr($unencoded_params['id'], $this->config->getPagePathTableName());
					$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', $this->config->getPagePathTableName(), 'alias=' . $str . ' AND deleted=0', '', '', 1);
					if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)) {
						$unencoded_params['id'] = $row['uid'];
					} else {
						return array();
					}
				}
			}

			$id = $unencoded_params['id'];

			try {
				$path = Tx_NaworkUri_Cache_TransformationCache::getTransformation('id', $id, $this->language);
			} catch (Tx_NaworkUri_Exception_TransformationValueNotFoundException $ex) {

				// get setup
				$limit = $this->config->getPagePathLimit();
				if (!$limit)
					$limit = 10;

				$field_conf = $this->config->getPagePathField();
				$field_conf = str_replace('//', ',', $field_conf);
				$fields = explode(',', 'tx_naworkuri_pathsegment,' . $field_conf);

				// determine language (system or link)
				$lang = 0;
				if (isset($original_params['L'])) {
					$lang = (int) $original_params['L'];
				}
				// walk the pagepath
				while ($limit && $id > 0) {

					$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(implode(',', $fields) . ',uid,pid,hidden,tx_naworkuri_exclude', $this->config->getPagePathTableName(), 'uid=' . $id . ' AND deleted=0', '', '', 1);
					$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres);
					if (!$row) {
						break; // no page found
					}

					// translate pagepath if needed
					if ($this->language > 0) {
						$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'pages_language_overlay', 'pid=' . $id . ' AND deleted=0 AND sys_language_uid=' . $this->language, '', '', 1);
						$translated_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres);
						foreach ($fields as $field) {
							if ($translated_row[$field]) {
								$row[$field] = $translated_row[$field];
							}
						}
					}
					// extract part
					if ($row['tx_naworkuri_exclude'] == 0) {
						if ($row['pid'] > 0) {
							foreach ($fields as $field) {
								if ($row[$field]) {
									$segment = trim($row[$field]);
									array_unshift($parts, $segment);
									break; // field found
								}
							}
						} elseif ($row['pid'] == 0 && $row['tx_naworkuri_pathsegment']) {
							$segment = trim($row['tx_naworkuri_pathsegment']);
							array_unshift($parts, $segment);
						}
					}
					// continue fetching the path
					$id = $row['pid'];
					$limit--;
				}
				$path = implode('/', $parts);
				Tx_NaworkUri_Cache_TransformationCache::setTransformation('id', $unencoded_params['id'], $path, $this->language);
			}
			$encoded_params['id'] = $unencoded_params['id'];
			unset($unencoded_params['id']);
		}


		return array('id' => $path);
	}

}

?>
