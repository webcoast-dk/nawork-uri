<?php

namespace Nawork\NaworkUri\Utility;

/*
 * Helper functions
 */

class GeneralUtility {

	const LOG_SEVERITY_INFO = 0;
	const LOG_SEVERITY_ERROR = 1;
	const LOG_SEVERITY_SYS = 2;
	const LOG_SEVERITY_SECURITY = 3;

	/**
	 * Tests if the input can be interpreted as integer.
	 *
	 * !!!this is a direct copy of the according method from typo3 4.6!!!
	 *
	 * @param $var mixed Any input variable to test
	 * @return boolean Returns TRUE if string is an integer
	 */
	public static function canBeInterpretedAsInteger($var) {
		if ($var === '') {
			return FALSE;
		}
		return (string) intval($var) === (string) $var;
	}

	/**
	 * Explode URI Parameters
	 *
	 * @param string $param_string Parameter Part of URI
	 * @return array Exploded Parameters
	 */
	public static function explode_parameters($param_string) {
		if (empty($param_string)) {
			return array();
		}

		$result = array();
		$tmp = explode('&', $param_string);
		foreach ($tmp as $part) {
			list($key, $value) = explode('=', $part);
			if (substr($key, -2) == '[]') {
				/* we have an array value */
				if (!array_key_exists($key, $result)) {
					$result[$key] = array();
				}
				$result[$key][] = $value;
			} else {
				$result[$key] = $value;
			}
		}
		krsort($result);
		return $result;
	}

	/**
	 * Implode URI Parameters
	 *
	 * @param array $params_array Parameter Array
	 * @param boolean $encode Return the parameters url encoded or not, default is yes
	 * 
	 * @return string Imploded Parameters
	 */
	public static function implode_parameters($params_array, $encode = TRUE) {
		self::arrayKsortRecursive($params_array);
		$queryStringParts = array();
		foreach ($params_array as $name => $value) {
			if (is_array($value)) {
				foreach ($value as $v) {
					$queryStringParts[] = ($encode ? rawurlencode($name) : $name) . '=' . ($encode ? rawurlencode($v) : $v);
				}
			} else {
				$queryStringParts[] = ($encode ? rawurlencode($name) : $name) . '=' . ($encode ? rawurlencode($value) : $value);
			}
		}
		return implode('&', $queryStringParts);
	}

	private static function arrayKsortRecursive(&$array) {
		uksort($array, array(self, 'compareArrayKeys'));
		foreach ($array as $key => $value) {
			if (is_array($value) && !empty($value)) {
				self::arrayKsortRecursive($value);
				$array[$key] = $value;
			}
		}
	}

	private static function compareArrayKeys($a, $b) {
		$a = strtolower($a);
		$b = strtolower($b);
		return strcmp($a, $b);
	}

	/**
	 * Sanitize the Path
	 *
	 * @param string $string
	 * @return string
	 */
	public static function sanitize_uri($uri) {
		$locale = self::getLocale();
		/* settings locales as in tsfe */
		if (strpos(strtolower($locale), 'tr') === FALSE) {
			setlocale(LC_CTYPE, $locale);
		}
		setlocale(LC_COLLATE, $locale);
		setlocale(LC_MONETARY, $locale);
		setlocale(LC_TIME, $locale);

		$uri = self::uriTransliterate($uri);
		$uri = strip_tags($uri);
		$uri = strtolower($uri);
		$uri = self::uri_handle_punctuation($uri);
		$uri = self::uri_handle_whitespace($uri);
		$uri = self::uri_limit_allowed_chars($uri);
		$uri = self::uri_make_wellformed($uri);

		return $uri;
	}

	public static function uriTransliterate($uri) {
		$config = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Nawork\NaworkUri\Configuration\ConfigurationReader');
		foreach ($config->getTransliterations() as $char) {
			$uri = str_replace((string) $char->attributes()->from, (string) $char->attributes()->to, $uri);
		}
		$uri = iconv('UTF-8', 'ASCII//TRANSLIT', $uri);
		return $uri;
	}

	/**
	 * Remove whitespace characters from uri
	 *
	 * @param string $uri
	 * @return string
	 */
	function uri_handle_whitespace($uri) {
		$uri = preg_replace('/[\s\-]+/u', '-', $uri);
		return $uri;
	}

	/**
	 * Convert punctuation chars to -
	 *  ! " # $ & ' ( ) * + , : ; < = > ? @ [ \ ] ^ ` { | } <-- Old
	 *
	 *  	" #   & '               <   > ? @ [ \ ] ^ ` { | } %   < -- New

	 *
	 * @param string $uri
	 * @return string
	 */
	function uri_handle_punctuation($uri) {
		$uri = preg_replace('/[\!\"\#\&\'\?\@\[\\\\\]\^\`\{\|\}\%\<\>\+]+/u', '-', $uri);
		return $uri;
	}

	/**
	 * remove not allowed chars from uri
	 * allowed chars A-Za-z0-9 - _ . ~ ! ( ) * + , : ; =
	 *
	 * @param unknown_type $uri
	 * @return unknown
	 */
	function uri_limit_allowed_chars($uri) {
		return preg_replace('/[^A-Za-z0-9\/\-\_\.\~\!\(\)\*\:\;\=]+/u', '', $uri);
	}

	/**
	 * Remove some ugly uri-formatings:
	 * - slashes from the Start
	 * - double slashes
	 * - -/ /-
	 *
	 * @param string $uri
	 * @return string
	 */
	function uri_make_wellformed($uri) {
		$uri = preg_replace('/[\-]+/', '-', $uri);
		$uri = preg_replace('/\/-/', '/', $uri);
		$uri = preg_replace('/-\//', '/', $uri);
		$uri = preg_replace('/[\/]+/', '/', $uri);
		$uri = preg_replace('/\-+/', '-', $uri);
		$uri = preg_replace('/^[\/]+/u', '', $uri);
		$uri = preg_replace('/\-$/', '', $uri);
		return $uri;
	}

	public static function isActiveBeUserSession() {
		if (array_key_exists('be_typo_user', $_COOKIE) && !empty($_COOKIE['be_typo_user'])) {
			$tstamp = time() - $GLOBALS['TYPO3_CONF_VARS']['BE']['sessionTimeout'];
			$beSessionResult = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'be_sessions', 'ses_id=\'' . $GLOBALS['TYPO3_DB']->quoteStr($_COOKIE['be_typo_user'], 'be_sessions') . '\' AND ses_tstamp>' . $tstamp);
			if (count($beSessionResult) == 1) {
				return true;
			}
		}
		return false;
	}

	public static function getCurrentDomain() {
		/* @var $config \Nawork\NaworkUri\Configuration\ConfigurationReader */
		$config = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Nawork\NaworkUri\Configuration\ConfigurationReader');
		/* @var $db \TYPO3\CMS\Core\Database\DatabaseConnection */
		$db = $GLOBALS['TYPO3_DB'];
		$domain = 0;
		if ($config->isMultiDomainEnabled()) {
			$domainName = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_HOST');
			$domainRes = $db->exec_SELECTgetRows('uid,tx_naworkuri_masterDomain', $config->getDomainTable(), 'domainName LIKE \'' . $domainName . '\'', 'hidden=0');
			if ($domainRes && count($domainRes)) {
				$domain = $domainRes[0]['uid'];
				if (intval($domainRes[0]['tx_naworkuri_masterDomain']) > 0) {
					$domain = intval($domainRes[0]['tx_naworkuri_masterDomain']);
					$continue = TRUE;
					do {
						$domainRes = $db->exec_SELECTgetRows('uid,tx_naworkuri_masterDomain', $config->getDomainTable(), 'uid=' . intval($domain));
						if (is_array($domainRes) && count($domainRes) > 0) {
							if (intval($domainRes[0]['tx_naworkuri_masterDomain']) > 0) {
								$domain = intval($domainRes[0]['tx_naworkuri_masterDomain']);
							} else {
								$domain = $domainRes[0]['uid'];
								$continue = FALSE;
							}
						}
					} while ($continue);
				}
			} else { // try to find the first domain record
				$domain = static::findDomainRecursive();
			}
		}
		return $domain;
	}

	private static function findDomainRecursive($pid = 0) {
		/* @var $db \TYPO3\CMS\Core\Database\DatabaseConnection */
		$db = $GLOBALS['TYPO3_DB'];
		/* @var $config \Nawork\NaworkUri\Configuration\ConfigurationReader */
		$config = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Nawork\NaworkUri\Configuration\ConfigurationReader');
		$pagesRes = $db->exec_SELECTgetRows('uid', $config->getPageTable(), 'pid=' . intval($pid), NULL, 'sorting ASC');
		if (is_array($pagesRes)) {
			foreach ($pagesRes as $pageRec) {
				$domainRes = $db->exec_SELECTgetRows('uid', $config->getDomainTable(), 'pid=' . intval($pageRec['uid']) . ' AND tx_naworkuri_masterDomain < 1', NULL, 'sorting ASC', '1');
				if ($domainRes && count($domainRes) > 0) {
					return $domainRes[0]['uid'];
				} else {
					return static::findDomainRecursive($pageRec['uid']);
				}
			}
		}
	}

	/**
	 *
	 * @param string $url The original url
	 * @param boolean $forRedirect Should be for a redirect or not
	 * @return string The finalized url
	 */
	public static function finalizeUrl($url, $forRedirect = FALSE) {
		$prefix = '';
		if ($forRedirect) {
			$prefix = '/';
			if (!empty($GLOBALS['TSFE']->config['config']['baseURL']))
				$prefix = $GLOBALS['TSFE']->config['config']['baseURL'];
		} else {
			if (!empty($GLOBALS['TSFE']->config['config']['absRefPrefix']))
				$prefix = $GLOBALS['TSFE']->config['config']['absRefPrefix'];
		}
		return $prefix . $url;
	}

	public static function sendRedirect($url, $status) {
		header('X-Redirect-By: nawork_uri');
		header('Location: ' . $url, true, $status);
		exit(0);
	}

	public static function getLocale() {
		$locale = $GLOBALS['TSFE']->config['config']['locale_all'];
		if (empty($locale)) {
			$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nawork_uri']);
			$locale = $extConf['default_locale'];
		}
		if (empty($locale)) {
			$locale = 'en_US';
		}
		return $locale;
	}

	/**
	 * This function filters the given parameters if there exists a configuration for it.
	 * If the parameter is encoded with a valuemap the values are checked too to avoid are lookup
	 * with a value that is not encoded
	 *
	 * @param array $parameters
	 */
	public static function filterConfiguredParameters($parameters) {
		$encodableParameters = array();
		$unencodableParameters = array();
		$parameterNames = array_keys($parameters);
		$configuration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Nawork\NaworkUri\Configuration\ConfigurationReader');
		/* @var $configuration \Nawork\NaworkUri\Configuration\ConfigurationReader */
		/* check predefined parts */
		foreach ($configuration->getPredefinedParts() as $parameterConfiguration) {
			/* @var $parameterConfiguration SimpleXMLElement */
			$parameterName = (string) $parameterConfiguration->parameter;
			if (in_array($parameterName, $parameterNames)) {
				$encodableParameters[$parameterName] = $parameters[$parameterName];
			}
		}
		/* check the uri parts */
		foreach ($configuration->getUriParts() as $parameterConfiguration) {
			$parameterName = (string) $parameterConfiguration->parameter;
			if (in_array($parameterName, $parameterNames)) {
				$encodableParameters[$parameterName] = $parameters[$parameterName];
			}
		}
		/* check the value maps */
		foreach ($configuration->getValueMaps() as $parameterConfiguration) {
			$parameterName = (string) $parameterConfiguration->parameter;
			if (in_array($parameterName, $parameterNames)) {
				foreach ($parameterConfiguration->children() as $child) {
					/* @var $child SimpleXMLElement */
					if ($child->getName() == 'value') {
						if ((string) $child == $parameters[$parameterName]) {
							$encodableParameters[$parameterName] = $parameters[$parameterName];
						}
					}
				}
			}
		}
		/* push the id */
		$encodableParameters['id'] = self::aliasToId($parameters['id']);
		ksort($encodableParameters);
		if (count($parameters) > count($encodableParameters) && array_key_exists('cHash', $encodableParameters)) {
			unset($encodableParameters['cHash']);
		}
		$unencodableParameters = array_diff_key($parameters, $encodableParameters);
		return array($encodableParameters, $unencodableParameters);
	}

	public static function aliasToId($alias) {
		if (self::canBeInterpretedAsInteger($alias)) {
			return $alias;
		}
		$db = $GLOBALS['TYPO3_DB'];
		$configuration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Nawork\NaworkUri\Configuration\ConfigurationReader');
		/* @var $db \TYPO3\CMS\Core\Database\DatabaseConnection */
		/* @var $configuration \Nawork\NaworkUri\Configuration\ConfigurationReader */
		$result = $db->exec_SELECTgetRows('uid', $configuration->getPageTable(), 'alias=' . $db->fullQuoteStr($alias, $configuration->getPageTable()) . ' AND deleted=0');
		if (is_array($result) && count($result) > 0) {
			return $result[0]['uid'];
		}
		return $alias;
	}

	public static function log($msg, $severity = self::LOG_SEVERITY_INFO) {
		$db = $GLOBALS['TYPO3_DB'];
		/* @var $db \TYPO3\CMS\Core\Database\DatabaseConnection */
		$db->exec_INSERTquery('sys_log', array(
			'details' => $msg,
			'error' => $severity,
			'tstamp' => time()
			), array(
			'error',
			'tstamp'
		));
	}

	/**
	 * Register transformation services.
	 * 
	 * @param string $type The name of the transformation type as used in the configuration, e.g. "ValueMap"
	 * @param string $classReference The class reference to the transformation service, e.g. "EXT:myext/Classes/Service/MyTransformationService.php:My\MyExt\Service\MyTransformationService
	 */
	public static function registerTransformationService($type, $classReference) {
		if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['transformationServices'])) {
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['transformationServices'] = array();
		}
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['transformationServices'][$type] = $classReference;
	}

}

?>
