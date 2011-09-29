<?php

require_once 'lib/class.tx_naworkuri_transformer.php';

class tx_naworkuri {

	/**
	 * decode uri and extract parameters
	 *
	 * @param unknown_type $params
	 * @param unknown_type $ref
	 */
	function uri2params($params, $ref) {
		global $TYPO3_CONF_VARS;


		if (
				$params['pObj']->siteScript
				&& substr($params['pObj']->siteScript, 0, 9) != 'index.php'
				&& substr($params['pObj']->siteScript, 0, 1) != '?'
		) {

			$uri = $params['pObj']->siteScript;
			list($uri, $parameters) = t3lib_div::trimExplode('?', $uri);

			// translate uri
			$extConf = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['nawork_uri']);
			/* @var $configReader tx_naworkuri_configReader */
			$configReader = t3lib_div::makeInstance('tx_naworkuri_configReader', $extConf['XMLPATH']);
			/* @var $translator tx_naworkuri_transformer */
			$translator = t3lib_div::makeInstance('tx_naworkuri_transformer', $configReader, $extConf['MULTIDOMAIN']);
			try {
				$uri_params = $translator->uri2params($uri);
				/* should the path be converted to lowercase to treat uppercase paths like normal paths */
				if (($configReader->getCheckForUpperCaseURI() && $uri == strtolower($uri)) || !$configReader->getCheckForUpperCaseURI()) {
					if (is_array($uri_params)) { // uri found
						$params['pObj']->id = $uri_params['id'];
						unset($uri_params['id']);
						$params['pObj']->mergingWithGetVars($uri_params);
					} else { // handle 404
						if ($configReader->hasPageNotFoundConfig()) {
							header('Content-Type: text/html; charset=utf-8');
							header($configReader->getPageNotFoundConfigStatus());
							switch ($configReader->getPageNotFoundConfigBehaviorType()) {
								case 'message':
									$res = $configReader->getPageNotFoundConfigBehaviorValue();
									break;
								case 'page':
									if (t3lib_div::getIndpEnv('HTTP_USER_AGENT') != 'nawork_uri') {
										$curl = curl_init();
										curl_setopt($curl, CURLOPT_URL, $configReader->getPageNotFoundConfigBehaviorValue());
										curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
										curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
										curl_setopt($curl, CURLOPT_TIMEOUT, 30);
										curl_setopt($curl, CURLOPT_USERAGENT, 'nawork_uri');
										$res = $this->curl_exec_follow($curl);
									} else {
										$res = '404 not found! The 404 Page URL ' . $configReader->getPageNotFoundConfigBehaviorValue() . ' seems to cause a redirect loop.';
									}
									break;
								case 'redirect':
									$path = html_entity_decode($configReader->getPageNotFoundConfigBehaviorValue());
									if (!($_SERVER['REQUEST_METHOD'] == 'POST' && preg_match('/index.php/', $_SERVER['SCRIPT_NAME']))) {
										header('Location: ' . $path, true, 301);
										exit;
									}
								default:
									$res = '';
							}
							echo $res;
							exit;
						}
					}
				}
			} catch (Tx_NaworkUri_Exception_UrlIsRedirectException $ex) {
				$url = $ex->getUrl();
				if ($url['type'] == tx_naworkuri_cache::TX_NAWORKURI_URI_TYPE_OLD) {
					$newUrlParameters = array('id' => $url['page_uid'], 'L' => $url['sys_language_uid']);
					if (!empty($url['params'])) {
						$newUrlParameters = array_merge($newUrlParameters, tx_naworkuri_helper::explode_parameters($url['params']));
					}
					$newUrl = $translator->params2uri(tx_naworkuri_helper::implode_parameters($newUrlParameters), TRUE, TRUE);
					$newUrl = tx_naworkuri_helper::finalizeUrl($newUrl);
					/* prepend a slash to make it a valid url */
					if (substr($newUrl, 0, 1) != '/') {
						$newUrl = '/' . $newUrl;
					}
					/* parse the current request url and prepend the scheme and host to the url */
					$requestUrl = t3lib_div::getIndpEnv('TYPO3_REQUEST_URL');
					$protocol = parse_url($requestUrl, PHP_URL_SCHEME);
					$host = parse_url($requestUrl, PHP_URL_HOST);
					$newUrl = $protocol . '://' . $host . $newUrl;
					tx_naworkuri_helper::sendRedirect($newUrl, 301);
				} elseif ($url['type'] == tx_naworkuri_cache::TX_NAWORKURI_URI_TYPE_REDIRECT) {
					$newUrl = parse_url($url['redirect_path']);
					$requestUrl = parse_url(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'));
					if (empty($newUrl['host']))
						$newUrl['host'] = $requestUrl['host'];
					if (empty($newUrl['scheme']))
						$newUrl['scheme'] = $requestUrl['scheme'];
					if (substr($newUrl['path'], 0, 1) != '/')
						$newUrl['path'] = '/' . $newUrl['path'];
					tx_naworkuri_helper::sendRedirect($newUrl['scheme'].'://'.$newUrl['host'].$newUrl['path'], $url['redirect_mode']);
				}
			}
		}
	}

	/**
	 * This function takes the link config and the tsfe as arguments and initializes the conversion of
	 * the totalURL to a path
	 *
	 * @param array $link
	 * @param tslib_fe $ref
	 */
	function params2uri(&$link, $ref) {
		global $TYPO3_CONF_VARS;
		if (
				$GLOBALS['TSFE']->config['config']['tx_naworkuri.']['enable'] == 1
				&& $link['LD']['url']
		) {
			list($path, $params) = explode('?', $link['LD']['totalURL']);
			if (t3lib_div::int_from_ver(TYPO3_version) > 4002000) {
				$params = urldecode($params);
			}
			$extConf = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['nawork_uri']);
			$configReader = t3lib_div::makeInstance('tx_naworkuri_configReader', $extConf['XMLPATH']);
			$translator = t3lib_div::makeInstance('tx_naworkuri_transformer', $configReader, (boolean) $extConf['MULTIDOMAIN']);
			$url = $translator->params2uri($params);
			$link['LD']['totalURL'] = $GLOBALS['TSFE']->config['config']['absRefPrefix'] . $url;
			/* add hook for post processing the url */
			if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['tx_naworkuri']['url-postProcess'])) {
				$helper = t3lib_div::makeInstance('tx_naworkuri_helper');
				$hookParams = array(
					'url' => $url,
					'params' => $helper->explode_parameters($params),
					'LD' => $link['LD']
				);
				foreach ($TYPO3_CONF_VARS['SC_OPTIONS']['tx_naworkuri']['url-postProcess'] as $funcRef) {
					t3lib_div::callUserFunction($funcRef, $hookParams, $this);
				}
				if ($hookParams['url'] !== FALSE) { // if the url is not false set it
					$link['LD']['totalURL'] = $hookParams['url'];
				}
			}
			if(!preg_match('/https?:\/\//', $link['LD']['totalURL']) && !empty($GLOBALS['TSFE']->config['config']['absRefPrefix'])) {
				if(substr($link['LD']['totalURL'], 0, strlen($GLOBALS['TSFE']->config['config']['absRefPrefix'])) != $GLOBALS['TSFE']->config['config']['absRefPrefix']) {
					$link['LD']['totalURL'] = $GLOBALS['TSFE']->config['config']['absRefPrefix'].$link['LD']['totalURL'];
				}
			}
		}
	}

	/**
	 * This function is used for two purposes. The first purpose is to redirect if the page is called via parameterized
	 * form, like "index.php?id=...", to the path form. The second purpose is to redirect if the type or L parameter
	 * are not valid, e.g. the type parameter contains "%25252525252523header" or something other non useful content.
	 *
	 * The first type only happens if the site is called via 'index.php?id=...' or '?id=...'
	 * The second type of redirect is sent if the parameters are checked and not seen as valid.
	 *
	 * Whatever redirect is sent, the state of enable and redirect option of nawork_uri in config are checked. Additionally
	 * it is checked that the page is not called as preview from admin panel and there is a sitescript at all.
	 *
	 * @param unknown_type $params
	 * @param tslib_fe $ref
	 */
	function redirect2uri($params, $ref) {
		global $TYPO3_CONF_VARS;
		if (
				$GLOBALS['TSFE']->config['config']['tx_naworkuri.']['enable'] == 1
				&& empty($_GET['ADMCMD_prev'])
				&& $GLOBALS['TSFE']->config['config']['tx_naworkuri.']['redirect'] == 1
				&& $GLOBALS['TSFE']->siteScript
		) {
			list($path, $params) = explode('?', $GLOBALS['TSFE']->siteScript);
			$extConf = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['nawork_uri']);
			$configReader = t3lib_div::makeInstance('tx_naworkuri_configReader', $extConf['XMLPATH']);
			$translator = t3lib_div::makeInstance('tx_naworkuri_transformer', $configReader, $extConf['MULTIDOMAIN']);
			$tempParams = tx_naworkuri_helper::explode_parameters($params);

			/* should the path be converted to lowercase to treat uppercase paths like normal paths */
			if ($configReader->getCheckForUpperCaseURI()) {
				if ($path != strtolower($path)) {
					$uri = $GLOBALS['TSFE']->config['config']['baseURL'] . strtolower($path);
					if (empty($uri)) {
						$uri = '/';
					}
					if (!empty($params)) {
						$uri .= '?' . $params;
					}
					header('Location: ' . $uri, true, $configReader->getRedirectStatus());
					exit;
				}
			}

			/* check if type should be casted to int to avoid strange behavior when creating links */
			if ($configReader->getCastTypeToInt()) {
				$type = !empty($tempParams['type']) ? $tempParams['type'] : t3lib_div::_GP('type');
				if (!empty($type) && !t3lib_div::testInt($type)) { // if type is not an int
					unset($tempParams['type']); // unset type param to use system default
					/* should we redirect if the parameter is wrong */
					if ($configReader->getRedirectOnParameterDiff()) {
						$uri = $GLOBALS['TSFE']->config['config']['baseURL'] . $path;
						if (empty($uri)) {
							$uri = '/';
						}
						if (count($tempParams) > 0) {
							$uri .= '?' . tx_naworkuri_helper::implode_parameters($tempParams);
						}
						header('Location: ' . $uri, true, $configReader->getRedirectStatus());
						exit;
					}
				}
			}

			/* check if L should be casted to int to avoid strange behavior when creating links */
			if ($configReader->getCastLToInt()) {
				$L = !empty($tempParams['L']) ? $tempParams['L'] : t3lib_div::_GP('L');
				if (!empty($L) && !t3lib_div::testInt($L)) { // if L is not an int
					unset($tempParams['L']); // unset L param to use system default
					/* should we redirect if the parameter is wrong */
					if ($configReader->getRedirectOnParameterDiff()) {
						$uri = $GLOBALS['TSFE']->config['config']['baseURL'] . $path;
						if (empty($uri)) {
							$uri = '/';
						}
						if (count($tempParams) > 0) {
							$uri .= '?' . tx_naworkuri_helper::implode_parameters($tempParams);
						}
						header('Location: ' . $uri, true, $configReader->getRedirectStatus());
						exit;
					}
				}
			}

			/* if the page is called via parameterized form look for a path to redirect to */
			if ((substr($GLOBALS['TSFE']->siteScript, 0, 9) == 'index.php' || substr($GLOBALS['TSFE']->siteScript, 0, 1) == '?')) {
				$dontCreateNewUrls = true;
				$tempParams = tx_naworkuri_helper::explode_parameters($params);
				if ((count($tempParams) < 3 && array_key_exists('L', $tempParams) && array_key_exists('id', $tempParams)) || (count($tempParams) < 2 && array_key_exists('id', $tempParams))) {
					if (tx_naworkuri_helper::isActiveBeUserSession()) {
						$dontCreateNewUrls = false;
					}
				}
				$ignoreTimeout = true;
				$uri = $translator->params2uri($params, $dontCreateNewUrls, $ignoreTimeout);
				if (!($_SERVER['REQUEST_METHOD'] == 'POST') && ($path == 'index.php' || $path == '') && $uri !== false) {
					if (empty($uri)) {
						$uri = ($GLOBALS['TSFE']->config['config']['baseURL'] ? $GLOBALS['TSFE']->config['config']['baseURL'] : ($GLOBALS['TSFE']->config['config']['absRefPrefix'] ? $GLOBALS['TSFE']->config['config']['baseURL'] : '/'));
					}
					header('Location: ' . $GLOBALS['TSFE']->config['config']['baseURL'] . $uri, true, 301);
					exit;
				}
			}
		}
	}

	/**
	 * Update the md5 values automatically
	 *
	 * @param unknown_type $incomingFieldArray
	 * @param unknown_type $table
	 * @param unknown_type $id
	 * @param unknown_type $res
	 */
	public function processDatamap_preProcessFieldArray(&$incomingFieldArray, &$table, &$id, &$res) {
		if ($table == "tx_naworkuri_uri") {
			if ($incomingFieldArray['path'] || $incomingFieldArray['path'] == '')
				$incomingFieldArray['hash_path'] = md5($incomingFieldArray['path']);
			if ($incomingFieldArray['params'] || $incomingFieldArray['params'] == '')
				$incomingFieldArray['hash_params'] = md5($incomingFieldArray['params']);
		}
	}

	function curl_exec_follow($ch, &$maxredirect = null) {
		$mr = $maxredirect === null ? 5 : intval($maxredirect);
		if (ini_get('open_basedir') == '' && ini_get('safe_mode') == '') {
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $mr > 0);
			curl_setopt($ch, CURLOPT_MAXREDIRS, $mr);
		} else {
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
			if ($mr > 0) {
				$newurl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

				$rch = curl_copy_handle($ch);
				curl_setopt($rch, CURLOPT_HEADER, true);
				curl_setopt($rch, CURLOPT_NOBODY, true);
				curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
				curl_setopt($rch, CURLOPT_RETURNTRANSFER, true);
				do {
					curl_setopt($rch, CURLOPT_URL, $newurl);
					$header = curl_exec($rch);
					if (curl_errno($rch)) {
						$code = 0;
					} else {
						$code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
						if ($code == 301 || $code == 302) {
							preg_match('/Location:(.*?)\n/', $header, $matches);
							$newurl = trim(array_pop($matches));
						} else {
							$code = 0;
						}
					}
				} while ($code && --$mr);
				curl_close($rch);
				if (!$mr) {
					if ($maxredirect === null) {
						trigger_error('Too many redirects. When following redirects, libcurl hit the maximum amount.', E_USER_WARNING);
					} else {
						$maxredirect = 0;
					}
					return false;
				}
				curl_setopt($ch, CURLOPT_URL, $newurl);
			}
		}
		return curl_exec($ch);
	}

}

?>
