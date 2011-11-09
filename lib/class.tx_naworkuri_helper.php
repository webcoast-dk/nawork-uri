<?php

/*
 * Helper functions
 */

class tx_naworkuri_helper {

	/**
	 * Explode URI Parameters
	 *
	 * @param string $param_string Parameter Part of URI
	 * @return array Exploded Parameters
	 */
	public static function explode_parameters($param_string) {
		/*
		  $res = array();
		  parse_str($param_string, $res);
		  return $res;
		 */
		$result = array();
		$tmp = explode('&', $param_string);
		foreach ($tmp as $part) {
			list($key, $value) = explode('=', $part);
			$result[$key] = $value;
		}
		ksort($result);
		return $result;
	}

	/**
	 * Implode URI Parameters
	 *
	 * @param array $params_array Parameter Array
	 * @return string Imploded Parameters
	 */
	public static function implode_parameters($params_array) {
		ksort($params_array);
		$result = '';
		$i = 0;
		foreach ($params_array as $key => $value) {
			if ($i > 0)
				$result .= '&';
			$result .= $key . '=' . $value;
			$i++;
		}
		return $result;
	}

	/**
	 * Sanitize the Path
	 *
	 * @param string $string
	 * @return string
	 */
	public function sanitize_uri($uri) {
		setlocale(LC_ALL, tx_naworkuri_helper::getLocale());
		$uri = $this->uriTransliterate($uri);
		$uri = strip_tags($uri);
		$uri = strtolower($uri);
		$uri = $this->uri_handle_punctuation($uri);
		$uri = $this->uri_handle_whitespace($uri);
		$uri = $this->uri_limit_allowed_chars($uri);
		$uri = $this->uri_make_wellformed($uri);

		return $uri;
	}

	public function uriTransliterate($uri) {
		$config = t3lib_div::makeInstance('tx_naworkuri_configReader');
		foreach($config->getTransliterations() as $char) {
			$uri = str_replace((string)$char->attributes()->from, (string)$char->attributes()->to, $uri);
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
		$config = t3lib_div::makeInstance('tx_naworkuri_configReader');
		$db = $GLOBALS['TYPO3_DB'];
		if ($config->isMultiDomainEnabled()) {
			$domain = t3lib_div::getIndpEnv('TYPO3_HOST_ONLY');
			$domainRes = $db->exec_SELECTgetRows('tx_naworkuri_masterDomain', $config->getDomainTable(), 'domainName LIKE \'' . $domain . '\'');
			if ($domainRes) {
				$uid = $domainRes[0]['tx_naworkuri_masterDomain'];
				$domainRes = $db->exec_SELECTgetRows('domainName', $config->getDomainTable(), 'uid=' . intval($uid));
				if (is_array($domainRes) && count($domainRes) > 0) {
					$domain = $domainRes[0]['domainName'];
				}
			}
		} else {
			$domain = '';
		}
		return $domain;
	}

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
	}

	public static function getLocale() {
		$encoding = $GLOBALS['TSFE']->config['config']['locale_all'];
		if (empty($encoding)) {
			$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nawork_uri']);
			$encoding = $extConf['default_locale'];
		}
		if (empty($encoding)) {
			$encoding = 'en_US';
		}
		return $encoding;
	}

}

?>
