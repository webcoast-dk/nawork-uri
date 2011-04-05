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

			// translate uri
			$extConf = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['nawork_uri']);
			/* @var $configReader tx_naworkuri_configReader */
			$configReader = t3lib_div::makeInstance('tx_naworkuri_configReader', $extConf['XMLPATH']);
			$translator = t3lib_div::makeInstance('tx_naworkuri_transformer', $configReader, $extConf['MULTIDOMAIN']);
			$uri_params = $translator->uri2params($uri);

			if ($uri_params) { // uri found
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
								curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
								$res = curl_exec($curl);
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
			$extConf = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['nawork_uri']);
			$configReader = t3lib_div::makeInstance('tx_naworkuri_configReader', $extConf['XMLPATH']);
			$translator = t3lib_div::makeInstance('tx_naworkuri_transformer', $configReader, (boolean) $extConf['MULTIDOMAIN']);
			$link['LD']['totalURL'] = $GLOBALS['TSFE']->config['config']['absRefPrefix'] . $translator->params2uri($params);
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
			$translator = t3lib_div::makeInstance('tx_naworkuri_transformer', $configReader);
			$tempParams = tx_naworkuri_helper::explode_parameters($params);
			/* check if type should be casted to int to avoid strange behavior when creating links */
			if ($configReader->getCastTypeToInt()) {
				$type = !empty($tempParams['type']) ? $tempParams['type'] : t3lib_div::_GP('type');
				if (!empty($type) && !t3lib_div::testInt($type)) { // if type is not an int
					unset($tempParams['type']); // unset type param to use system default
					/* should we redirect if the parameter is wrong */
					if ($configReader->getRedirectOnParameterDiff()) {
						$uri = $GLOBALS['TSFE']->config['config']['baseURL'] . $path;
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
						if (count($tempParams) > 0) {
							$uri .= '?' . tx_naworkuri_helper::implode_parameters($tempParams);
						}
						header('Location: ' . $uri, true, $configReader->getRedirectStatus());
						exit;
					}
				}
			}
			if ((substr($GLOBALS['TSFE']->siteScript, 0, 9) == 'index.php' || substr($GLOBALS['TSFE']->siteScript, 0, 1) == '?')) {
				$dontCreateNewUrls = true;
				$tempParams = tx_naworkuri_helper::explode_parameters($params);
				if ((count($tempParams) < 3 && array_key_exists('L', $tempParams) && array_key_exists('id', $tempParams)) || (count($tempParams) < 2 && array_key_exists('id', $tempParams))) {
					if (tx_naworkuri_helper::isActiveBeUserSession()) {
						$dontCreateNewUrls = false;
					}
				}
				$uri = $translator->params2uri($params, $dontCreateNewUrls);
				if (!($_SERVER['REQUEST_METHOD'] == 'POST') && ($path == 'index.php' || $path == '') && $uri !== false) {
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

}

?>
