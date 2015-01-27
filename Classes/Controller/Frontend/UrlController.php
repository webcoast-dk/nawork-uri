<?php

namespace Nawork\NaworkUri\Controller\Frontend;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;

class UrlController implements \TYPO3\CMS\Core\SingletonInterface {

	protected $redirectUrl = NULL;

	/**
	 * decode uri and extract parameters
	 *
	 * @param unknown_type                                               $params
	 * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $ref
	 */
	function uri2params($params, $ref) {
		global $TYPO3_CONF_VARS;

		if (!\Nawork\NaworkUri\Utility\ConfigurationUtility::getConfiguration()->getGeneralConfiguration()->getDisabled()) {

			if ($params['pObj']->siteScript && substr($params['pObj']->siteScript, 0, 9) != 'index.php' && substr($params['pObj']->siteScript, 0, 1) != '?') {
				$uri = $params['pObj']->siteScript;
				list($uri, $parameters) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('?', $uri);
				// translate uri
				$extConf = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['nawork_uri']);
				/* @var $translator \Nawork\NaworkUri\Utility\TransformationUtility */
				$translator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Nawork\NaworkUri\Utility\TransformationUtility', $configReader, $extConf['MULTIDOMAIN']);
				try {
					$uri_params = $translator->uri2params($uri);
					if (is_array($uri_params)) { // uri found
						$params['pObj']->id = $uri_params['id'];
						unset($uri_params['id']);
						$params['pObj']->mergingWithGetVars($uri_params);
					} else { // handle 404
						$this->handlePagenotfound(array('currentUrl' => $ref->siteScript, 'reaseonText' => 'The requested path could not be found', 'pageAccessFailureReasons' => array()), $ref);
					}
				} catch (\Nawork\NaworkUri\Exception\UrlIsRedirectException $ex) {
					$this->redirectUrl = $ex->getUrl();
				}
			}
		}
	}

	/**
	 * This function takes the link config and the tsfe as arguments and initializes the conversion of
	 * the totalURL to a path
	 *
	 * @param array                                                       $link
	 * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $ref
	 */
	function params2uri(&$link, $ref) {
		global $TYPO3_CONF_VARS;
		if (!\Nawork\NaworkUri\Utility\ConfigurationUtility::getConfiguration()->getGeneralConfiguration()->getDisabled() && $link['LD']['url']) {
			list($path, $params) = explode('?', $link['LD']['totalURL']);
			$extConf = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['nawork_uri']);
			/** @var \Nawork\NaworkUri\Utility\TransformationUtility $translator */
			$translator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Nawork\NaworkUri\Utility\TransformationUtility');
			try {
				$url = $translator->params2uri($params);
				$link['LD']['totalURL'] = \Nawork\NaworkUri\Utility\GeneralUtility::finalizeUrl($url);
				/* add hook for post processing the url */
				if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['tx_naworkuri']['url-postProcess'])) {
					$hookParams = array('url' => $url, 'params' => \Nawork\NaworkUri\Utility\GeneralUtility::explode_parameters($params), 'LD' => $link['LD']);
					foreach ($TYPO3_CONF_VARS['SC_OPTIONS']['tx_naworkuri']['url-postProcess'] as $funcRef) {
						\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $hookParams, $this);
					}
					if ($hookParams['url'] !== FALSE) { // if the url is not false set it
						$link['LD']['totalURL'] = $hookParams['url'];
					}
				}
				if (!preg_match('/https?:\/\//', $link['LD']['totalURL']) && !empty($GLOBALS['TSFE']->config['config']['absRefPrefix'])) {
					if (substr($link['LD']['totalURL'], 0, strlen($GLOBALS['TSFE']->config['config']['absRefPrefix'])) != $GLOBALS['TSFE']->config['config']['absRefPrefix']) {
						$link['LD']['totalURL'] = $GLOBALS['TSFE']->config['config']['absRefPrefix'] . $link['LD']['totalURL'];
					}
				}
			} catch (\Nawork\NaworkUri\Exception\UrlIsNotUniqueException $ex) {
				/* log unique failure to belog */
				\Nawork\NaworkUri\Utility\GeneralUtility::log('Url "' . $ex->getPath() . ' is not unique with parameters ' . \Nawork\NaworkUri\Utility\GeneralUtility::implode_parameters($ex->getParameters()), \Nawork\NaworkUri\Utility\GeneralUtility::LOG_SEVERITY_ERROR);
			} catch (\Nawork\NaworkUri\Exception\DbErrorException $ex) {
				/* log db errors to belog */
				\Nawork\NaworkUri\Utility\GeneralUtility::log('An database error occured while creating a url. The SQL error was: "' . $ex->getSqlError() . '"', \Nawork\NaworkUri\Utility\GeneralUtility::LOG_SEVERITY_ERROR);
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
	 * @param array                                                       $incomingParameters
	 * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $ref
	 */
	function redirect2uri($incomingParameters, $ref) {
		global $TYPO3_CONF_VARS;
		if (!\Nawork\NaworkUri\Utility\ConfigurationUtility::getConfiguration()->getGeneralConfiguration()->getDisabled()) {
			/*
			 * if we set a redirectUrl above because an old url was called we should
			 * redirect it here because at this point we have the full tsfe to get
			 * the correct target url
			 */
			if ($this->redirectUrl != NULL) {
				// translate uri
				$extConf = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['nawork_uri']);
				/* @var $translator \Nawork\NaworkUri\Utility\TransformationUtility */
				$translator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Nawork\NaworkUri\Utility\TransformationUtility');
				$newUrl = NULL;
				$redirectStatus = HttpUtility::HTTP_STATUS_301;
				// switch for determining the url
				switch((int)$this->redirectUrl['type']) {
					case 1:
						// get the id, language and parameters and try to find a current url to this set
						$newUrlParameters = array('id' => $this->redirectUrl['page_uid'], 'L' => $this->redirectUrl['sys_language_uid']);
						if (!empty($this->redirectUrl['params'])) {
							$newUrlParameters = array_merge($newUrlParameters, \Nawork\NaworkUri\Utility\GeneralUtility::explode_parameters($this->redirectUrl['params']));
						}
						// do not create new urls when trying to find one
						$newUrl = $translator->params2uri(\Nawork\NaworkUri\Utility\GeneralUtility::implode_parameters($newUrlParameters, FALSE), TRUE, TRUE);
						break;
					case 2:
						// use the redirect path set via the backend
						$newUrl = $this->redirectUrl['redirect_path'];
						break;
					case 3:
						$newUrlParameters = array_merge(\Nawork\NaworkUri\Utility\GeneralUtility::explode_parameters($this->redirectUrl['params']), array('id' => $this->redirectUrl['page_uid'], 'L' => $this->redirectUrl['sys_language_uid']));
						// try to find a new url or create one, if it does not exist
						$newUrl = $translator->params2uri(\Nawork\NaworkUri\Utility\GeneralUtility::implode_parameters($newUrlParameters, FALSE), FALSE, TRUE);
						break;

				}
				if($newUrl != NULL) {
					$newUrl = \Nawork\NaworkUri\Utility\GeneralUtility::finalizeUrl($newUrl, TRUE);
					// switch for determining the status code
					switch((int)$this->redirectUrl['type']) {
						case 1:
							$redirectStatus = HttpUtility::HTTP_STATUS_301;
							break;
						default:
							switch((int)$this->redirectUrl['redirect_mode']) {
								case 301:
									$redirectStatus = HttpUtility::HTTP_STATUS_301;
									break;
								case 302:
									$redirectStatus = HttpUtility::HTTP_STATUS_302;
									break;
								case 303:
									$redirectStatus = HttpUtility::HTTP_STATUS_303;
									break;
								case 307:
									$redirectStatus = HttpUtility::HTTP_STATUS_307;
									break;
							}
					}
					/* parse the current request url and prepend the scheme and host to the url */
					$requestUrl = parse_url(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
					$newUrl = parse_url($newUrl);
					if (empty($newUrl['scheme'])) {
						$newUrl['scheme'] = $requestUrl['scheme'];
					}
					if (empty($newUrl['host'])) {
						$newUrl['host'] = $requestUrl['host'];
					}
					if (substr($newUrl['path'], 0, 1) != '/') {
						$newUrl['path'] = '/' . $newUrl['path'];
					}
					$uri = $newUrl['scheme'] . '://' . $newUrl['host'] . $newUrl['path'];
					$queryParams = array_merge(
						\Nawork\NaworkUri\Utility\GeneralUtility::explode_parameters(
							rawurldecode($requestUrl['query'])
						),
						\Nawork\NaworkUri\Utility\GeneralUtility::explode_parameters($newUrl['query'])
					);
					if (!empty($queryParams)) {
						$uri .= '?' . \Nawork\NaworkUri\Utility\GeneralUtility::implode_parameters($queryParams);
					}
					if (array_key_exists('fragment', $newUrl) && !empty($newUrl['fragment'])) {
						$uri .= '#' . $newUrl['fragment'];
					}
					\Nawork\NaworkUri\Utility\GeneralUtility::sendRedirect($uri, $redirectStatus);
				}
			} elseif (!\Nawork\NaworkUri\Utility\ConfigurationUtility::getConfiguration()->getGeneralConfiguration()->getDisabled() && empty($_GET['ADMCMD_prev']) && $GLOBALS['TSFE']->siteScript) {
				list($path, $params) = explode('?', $GLOBALS['TSFE']->siteScript);
				$params = rawurldecode(html_entity_decode($params)); // decode the query string because it is expected by the further processing functions
				$extConf = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['nawork_uri']);
				$translator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Nawork\NaworkUri\Utility\TransformationUtility');
				$tempParams = \Nawork\NaworkUri\Utility\GeneralUtility::explode_parameters($params);

				/* if the page is called via parameterized form look for a path to redirect to */
				if ((substr($GLOBALS['TSFE']->siteScript, 0, 9) == 'index.php' || substr($GLOBALS['TSFE']->siteScript, 0, 1) == '?')) {
					$dontCreateNewUrls = TRUE;
					$ignoreTimeout = TRUE;
					$tempParams = \Nawork\NaworkUri\Utility\GeneralUtility::explode_parameters($params);
					if ((count($tempParams) < 3 && array_key_exists('L', $tempParams) && array_key_exists('id', $tempParams)) || (count($tempParams) < 2 && array_key_exists('id', $tempParams))) {
						if (\Nawork\NaworkUri\Utility\GeneralUtility::isActiveBeUserSession()) {
							$dontCreateNewUrls = FALSE;
							// set ignoreTimout to false to allow creation of new urls, e.g. after page title change
							$ignoreTimeout = FALSE;
						}
					}
					try {
						$uri = $translator->params2uri($params, $dontCreateNewUrls, $ignoreTimeout);
						if (in_array($_SERVER['REQUEST_METHOD'], array('GET','HEAD')) && ($path == 'index.php' || $path == '') && $uri !== FALSE && $uri != $GLOBALS['TSFE']->siteScript) {
							$uri = \Nawork\NaworkUri\Utility\GeneralUtility::finalizeUrl($uri, TRUE); // TRUE is for redirect, this applies "/" by default and the baseURL if set
							\Nawork\NaworkUri\Utility\GeneralUtility::sendRedirect($uri, \Nawork\NaworkUri\Utility\ConfigurationUtility::getConfiguration()->getGeneralConfiguration()->getRedirectStatus());
							exit;
						}
					} catch (\Nawork\NaworkUri\Exception\UrlIsNotUniqueException $ex) {
						/* log unique failure to belog */
						\Nawork\NaworkUri\Utility\GeneralUtility::log('Url "' . $ex->getPath() . ' is not unique with parameters ' . \Nawork\NaworkUri\Utility\GeneralUtility::implode_parameters($ex->getParameters()), \Nawork\NaworkUri\Utility\GeneralUtility::LOG_SEVERITY_ERROR);
					} catch (\Nawork\NaworkUri\Exception\DbErrorException $ex) {
						/* log db errors to belog */
						\Nawork\NaworkUri\Utility\GeneralUtility::log('An database error occured while creating a url. The SQL error was: "' . $ex->getSqlError() . '"', \Nawork\NaworkUri\Utility\GeneralUtility::LOG_SEVERITY_ERROR);
					}
				}
			}
		}
	}

	function curl_exec_follow($ch, &$maxredirect = NULL) {
		$mr = $maxredirect === NULL ? 5 : intval($maxredirect);
		if (ini_get('open_basedir') == '' && ini_get('safe_mode') == '') {
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $mr > 0);
			curl_setopt($ch, CURLOPT_MAXREDIRS, $mr);
		} else {
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
			if ($mr > 0) {
				$newurl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

				$rch = curl_copy_handle($ch);
				curl_setopt($rch, CURLOPT_HEADER, TRUE);
				curl_setopt($rch, CURLOPT_NOBODY, TRUE);
				curl_setopt($rch, CURLOPT_FORBID_REUSE, FALSE);
				curl_setopt($rch, CURLOPT_RETURNTRANSFER, TRUE);
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
					if ($maxredirect === NULL) {
						trigger_error('Too many redirects. When following redirects, libcurl hit the maximum amount.', E_USER_WARNING);
					} else {
						$maxredirect = 0;
					}

					return FALSE;
				}
				curl_setopt($ch, CURLOPT_URL, $newurl);
			}
		}

		return curl_exec($ch);
	}

	/**
	 * Handles the pagenotfound event:
	 * This function is called from tx_naworkuri_uri::uri2params if the path is not found.
	 * Additionally it can be used as a user function in $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling'], e.g.:
	 * USER_FUNCTION:EXT:nawork_uri/class.tx_naworkuri.php:&tx_naworkuri->handlePagenotfound.
	 *
	 * Two situations are supported. The page is not found, this is the case, if the path was not found or a
	 * non-existing page id is requested. The other case is, that a page is requested, that is not accessible without being
	 * logged in in the frontend. The handling case can be configured via <pageNotAccessible> tag in the configuration file.
	 * If this tag does not exist that pagenotfound configuration is used. So handling the page being not accessible is
	 * optional behavior.
	 *
	 * @param array                                                      $params
	 * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $frontendController
	 *
	 * @todo Handle not found and not accessible differently
	 */
	public function handlePagenotfound($params, $frontendController) {
		if (!\Nawork\NaworkUri\Utility\ConfigurationUtility::getConfiguration()->getGeneralConfiguration()->getDisabled()) {
			$output = '';
			$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nawork_uri']);
			/* the page is not accessible without being logged in, so handle this, if configured */
			if (array_key_exists('pageAccessFailureReasons', $params) && is_array($params['pageAccessFailureReasons']) && array_key_exists('fe_group', $params['pageAccessFailureReasons']) && \Nawork\NaworkUri\Utility\ConfigurationUtility::getConfiguration()->getPageNotAccessibleConfiguration() instanceof \Nawork\NaworkUri\Configuration\PageNotAccessibleConfiguration) {
				$pageNotAccessibleConfiguration = \Nawork\NaworkUri\Utility\ConfigurationUtility::getConfiguration()->getPageNotAccessibleConfiguration();
				header($pageNotAccessibleConfiguration->getStatus());
				header('Content-type: text/html; charset=utf8');
				switch ($pageNotAccessibleConfiguration->getBehavior()) {
					case \Nawork\NaworkUri\Configuration\PageNotAccessibleConfiguration::BEHAVIOR_MESSAGE:
						$output = $pageNotAccessibleConfiguration->getValue();
						break;
					case \Nawork\NaworkUri\Configuration\PageNotAccessibleConfiguration::BEHAVIOR_PAGE:
						if (\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_USER_AGENT') != 'nawork_uri') {
							$curl = curl_init();
							$urlParts = parse_url($pageNotAccessibleConfiguration->getValue());
							$urlParts['scheme'] = GeneralUtility::getIndpEnv('TYPO3_SSL') ? 'https' : 'http';
							$notFoundUrl = $urlParts['scheme'] . '://' . $urlParts['host'] . $urlParts['path'] . (!empty($urlParts['query']) ? '?' . $urlParts['query'] : '');
							curl_setopt($curl, CURLOPT_URL, $notFoundUrl);
							curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
							curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
							curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
							curl_setopt($curl, CURLOPT_TIMEOUT, 30);
							curl_setopt($curl, CURLOPT_USERAGENT, 'nawork_uri');
							curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
							curl_setopt($curl, CURLOPT_MAXREDIRS, 1);
							curl_setopt($curl, CURLOPT_REFERER, \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
							// disable check for valid peer certificate: this should not be used in
							// production environments for security reasons
							if ((bool) $extConf['noSslVerify']) {
								curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
								curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
							}
							$output = $this->curl_exec_follow($curl);
						} else {
							$output = '404 not found! The 404 Page URL ' . $pageNotAccessibleConfiguration->getValue() . ' seems to cause a loop.';
						}
						break;
					case \Nawork\NaworkUri\Configuration\PageNotAccessibleConfiguration::BEHAVIOR_REDIRECT:
						$path = html_entity_decode($pageNotAccessibleConfiguration->getValue());
						if (!($_SERVER['REQUEST_METHOD'] == 'POST' && preg_match('/index.php/', $_SERVER['SCRIPT_NAME']))) {
							\Nawork\NaworkUri\Utility\GeneralUtility::sendRedirect($path, 301); // send headers and exits
						}
					default:
						$output = '<html><head><title>403 Forbidden</title></head><body><h1>403 Forbidden</h1><p>You don\'t have the permission to access this page</p></body></html>';
				}
			} elseif (\Nawork\NaworkUri\Utility\ConfigurationUtility::getConfiguration()->getPageNotFoundConfiguration() instanceof \Nawork\NaworkUri\Configuration\PageNotFoundConfiguration) {
				$pageNotFoundConfiguration = \Nawork\NaworkUri\Utility\ConfigurationUtility::getConfiguration()->getPageNotFoundConfiguration();
				header('Content-Type: text/html; charset=utf-8');
				header($pageNotFoundConfiguration->getStatus());
				switch ($pageNotFoundConfiguration->getBehavior()) {
					case \Nawork\NaworkUri\Configuration\PageNotFoundConfiguration::BEHAVIOR_MESSAGE:
						$output = $pageNotFoundConfiguration->getValue();
						break;
					case \Nawork\NaworkUri\Configuration\PageNotFoundConfiguration::BEHAVIOR_PAGE:
						if (\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_USER_AGENT') != 'nawork_uri') {
							$curl = curl_init();
							$urlParts = parse_url($pageNotFoundConfiguration->getValue());
							$urlParts['scheme'] = GeneralUtility::getIndpEnv('TYPO3_SSL') ? 'https' : 'http';
							$notFoundUrl = $urlParts['scheme'] . '://' . $urlParts['host'] . $urlParts['path'] . (!empty($urlParts['query']) ? '?' . $urlParts['query'] : '');
							curl_setopt($curl, CURLOPT_URL, $notFoundUrl);
							curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
							curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
							curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
							curl_setopt($curl, CURLOPT_TIMEOUT, 30);
							curl_setopt($curl, CURLOPT_USERAGENT, 'nawork_uri');
							curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
							curl_setopt($curl, CURLOPT_MAXREDIRS, 1);
							curl_setopt($curl, CURLOPT_REFERER, \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
							// disable check for valid peer certificate: this should not be used in
							// production environments for security reasons
							if ((bool) $extConf['noSslVerify']) {
								curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
								curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
							}
							$output = $this->curl_exec_follow($curl);
						} else {
							$output = '404 not found! The 404 Page URL ' . $pageNotFoundConfiguration->getValue() . ' seems to cause a loop.';
						}
						break;
					case \Nawork\NaworkUri\Configuration\PageNotFoundConfiguration::BEHAVIOR_REDIRECT:
						$path = html_entity_decode($pageNotFoundConfiguration->getValue());
						if (!($_SERVER['REQUEST_METHOD'] == 'POST' && preg_match('/index.php/', $_SERVER['SCRIPT_NAME']))) {
							\Nawork\NaworkUri\Utility\GeneralUtility::sendRedirect($path, 301); // send headers and exits
						}
					default:
						$output = '';
				}
			} else {
				$output = '<html><head><title>404 Not found</title></head><body><h1>Not found!</h1><p>The page you are trying to access is not available</p></body></html>';
			}
			echo $output;
			exit(0);
		}
	}

}

?>
