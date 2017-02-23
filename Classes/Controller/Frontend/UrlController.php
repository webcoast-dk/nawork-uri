<?php

namespace Nawork\NaworkUri\Controller\Frontend;

use Nawork\NaworkUri\Configuration\Configuration;
use Nawork\NaworkUri\Configuration\PageNotAccessibleConfiguration;
use Nawork\NaworkUri\Configuration\PageNotFoundConfiguration;
use Nawork\NaworkUri\Exception\DbErrorException;
use Nawork\NaworkUri\Exception\TransformationServiceException;
use Nawork\NaworkUri\Exception\UrlIsNotUniqueException;
use Nawork\NaworkUri\Exception\UrlIsRedirectException;
use Nawork\NaworkUri\Hooks\UrlControllerHookInterface;
use Nawork\NaworkUri\Utility\ConfigurationUtility;
use Nawork\NaworkUri\Utility\TransformationUtility;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Frontend\Http\RequestHandler;

class UrlController implements SingletonInterface {

	protected $redirectUrl = NULL;

    protected $pageNotAccessibleHandlingInProgress = FALSE;
    protected $pageNotFoundHandlingInProgress = FALSE;

	/**
     * decode uri and extract parameters
     *
     * @param array                                                       $incomingParameters
     * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $ref
     */
    function uri2params($incomingParameters, $ref) {
        $configuration = ConfigurationUtility::getConfiguration();

		if ($configuration instanceof Configuration) {
			if ($incomingParameters['pObj']->siteScript && substr($incomingParameters['pObj']->siteScript, 0, 9) != 'index.php' && substr($incomingParameters['pObj']->siteScript, 0, 1) != '?' && !$this->pageNotFoundHandlingInProgress && !$this->pageNotAccessibleHandlingInProgress) {
				$uri = $incomingParameters['pObj']->siteScript;
				list($uri, ) = GeneralUtility::trimExplode('?', $uri);
				/* @var $transformationUtility \Nawork\NaworkUri\Utility\TransformationUtility */
				$transformationUtility = GeneralUtility::makeInstance(TransformationUtility::class);
				try {
					$uri_params = $transformationUtility->uri2params($uri);
					if (is_array($uri_params)) { // uri found
						$incomingParameters['pObj']->id = $uri_params['id'];
						unset($uri_params['id']);
						$incomingParameters['pObj']->mergingWithGetVars($uri_params);
					} else { // handle 404
						$this->handlePagenotfound(array('currentUrl' => $ref->siteScript, 'reasonText' => 'The requested path could not be found', 'pageAccessFailureReasons' => array('nawork_uri' => 'Url not found')), $ref);
					}
				} catch (UrlIsRedirectException $ex) {
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
		// if available, call hook for pre processing link data
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_naworkuri'][UrlController::class])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_naworkuri'][UrlController::class] as $hookObjectClassName) {
				$hookObject = GeneralUtility::getUserObj($hookObjectClassName);
				if (!$hookObject instanceof UrlControllerHookInterface) {
					throw new \RuntimeException(vsprintf('$hookObj of type ' . get_class($hookObject) . ' must implement %s', [UrlControllerHookInterface::class]));
				}
				$hookObject->params2uri_linkDataPreProcess($link, $ref);
			}
		}
		if(!empty($link['args']['targetDomain'])) {
			$domain = \Nawork\NaworkUri\Utility\GeneralUtility::getCurrentDomain($link['args']['targetDomain']);
			$domainName = $link['args']['targetDomain'];
		} else {
			$domain = \Nawork\NaworkUri\Utility\GeneralUtility::getCurrentDomain();
			$domainName = GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');
		}
	    $configuration = ConfigurationUtility::getConfiguration($domainName);
		if ($configuration instanceof Configuration && $link['LD']['url']) {
			list(, $params) = explode('?', $link['LD']['totalURL']);
			/** @var \Nawork\NaworkUri\Utility\TransformationUtility $transformationUtility */
			$transformationUtility = GeneralUtility::makeInstance(TransformationUtility::class);
			try {
				$transformationUtility->setDomain($domain);
				$url = $transformationUtility->params2uri($params);
				$link['LD']['totalURL'] = \Nawork\NaworkUri\Utility\GeneralUtility::finalizeUrl($url);
				/* add hook for post processing the url */
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_naworkuri']['url-postProcess'])) {
					$hookParams = array('url' => $url, 'parameters' => \Nawork\NaworkUri\Utility\GeneralUtility::explode_parameters($params), 'LD' => $link['LD']);
					foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_naworkuri']['url-postProcess'] as $funcRef) {
						GeneralUtility::callUserFunction($funcRef, $hookParams, $this);
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
			} catch (UrlIsNotUniqueException $ex) {
				/* log unique failure to belog */
				\Nawork\NaworkUri\Utility\GeneralUtility::log('Url "%s" is not unique with parameters %s. Referrer: %s', GeneralUtility::SYSLOG_SEVERITY_ERROR, array($ex->getPath(), \Nawork\NaworkUri\Utility\GeneralUtility::implode_parameters($ex->getParameters()), GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL')));
			} catch (DbErrorException $ex) {
				/* log db errors to belog */
				\Nawork\NaworkUri\Utility\GeneralUtility::log('An database error occured while creating a url. The SQL error was: "%s"', GeneralUtility::SYSLOG_SEVERITY_ERROR, array($ex->getSqlError()));
			} catch (TransformationServiceException $ex) {
                \Nawork\NaworkUri\Utility\GeneralUtility::log('A transformation could not completed: ' . $ex->getMessage() . ($ex->getPrevious() instanceof \Exception ? ' The previous error was:' . $ex->getPrevious()->getMessage(): ''));
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
	 */
	function redirect2uri() {
		$configuration = ConfigurationUtility::getConfiguration();
		if ($configuration instanceof Configuration) {
			/*
			 * if we set a redirectUrl above because an old url was called we should
			 * redirect it here because at this point we have the full tsfe to get
			 * the correct target url
			 */
			if ($this->redirectUrl != NULL) {
				// translate uri
				/* @var $translator \Nawork\NaworkUri\Utility\TransformationUtility */
				$translator = GeneralUtility::makeInstance(TransformationUtility::class);
				$newUrl = NULL;
				$redirectStatus = HttpUtility::HTTP_STATUS_301;
				// switch for determining the url
				switch((int)$this->redirectUrl['type']) {
					case 1:
						// get the id, language and parameters and try to find a current url to this set
						$newUrlParameters = array('id' => $this->redirectUrl['page_uid'], 'L' => $this->redirectUrl['sys_language_uid']);
						if (!empty($this->redirectUrl['parameters'])) {
							$newUrlParameters = array_merge($newUrlParameters, \Nawork\NaworkUri\Utility\GeneralUtility::explode_parameters($this->redirectUrl['parameters']));
						}
						// do not create new urls when trying to find one
						$newUrl = $translator->params2uri(\Nawork\NaworkUri\Utility\GeneralUtility::implode_parameters($newUrlParameters, FALSE), TRUE, TRUE);
						break;
					case 2:
						// use the redirect path set via the backend
						$newUrl = $this->redirectUrl['redirect_path'];
						break;
					case 3:
						$newUrlParameters = array_merge(\Nawork\NaworkUri\Utility\GeneralUtility::explode_parameters($this->redirectUrl['parameters']), array('id' => $this->redirectUrl['page_uid'], 'L' => $this->redirectUrl['sys_language_uid']));
						// try to find a new url or create one, if it does not exist
						$newUrl = $translator->params2uri(\Nawork\NaworkUri\Utility\GeneralUtility::implode_parameters($newUrlParameters, FALSE), FALSE, TRUE);
						break;

				}
				if($newUrl !== NULL) {
					$newUrl = \Nawork\NaworkUri\Utility\GeneralUtility::finalizeUrl($newUrl);
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
					$requestUrl = parse_url(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
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
			} elseif ($configuration instanceof Configuration && count(GeneralUtility::_GP('TSFE_ADMIN_PANEL')) == 0 && $GLOBALS['TSFE']->siteScript) {
				list($path, $params) = explode('?', $GLOBALS['TSFE']->siteScript);
				$params = rawurldecode(html_entity_decode($params)); // decode the query string because it is expected by the further processing functions
				$translator = GeneralUtility::makeInstance(TransformationUtility::class);

				/* if the page is called via parameterized form look for a path to redirect to */
				if ((substr($GLOBALS['TSFE']->siteScript, 0, 9) == 'index.php' || substr($GLOBALS['TSFE']->siteScript, 0, 1) == '?')) {
					$doNotCreateNewUrls = TRUE;
					$ignoreTimeout = TRUE;
                    if (\Nawork\NaworkUri\Utility\GeneralUtility::isActiveBeUserSession()) {
                        $doNotCreateNewUrls = FALSE;
                        // set ignoreTimeout to false to allow creation of new urls, e.g. after page title change
                        $ignoreTimeout = FALSE;
                    }
					try {
						$uri = $translator->params2uri($params, $doNotCreateNewUrls, $ignoreTimeout);
						if (in_array($_SERVER['REQUEST_METHOD'], array('GET','HEAD')) && ($path == 'index.php' || $path == '') && $uri !== FALSE && $uri != $GLOBALS['TSFE']->siteScript) {
							$uri = \Nawork\NaworkUri\Utility\GeneralUtility::finalizeUrl($uri); // TRUE is for redirect, this applies "/" by default and the baseURL if set
                            $redirectStatus = $configuration->getGeneralConfiguration()->getRedirectStatus();
                            // if the redirect status in the configuration is an integer, e.g. "301" try to get the correct value from the constant
                            if (MathUtility::canBeInterpretedAsInteger($redirectStatus)) {
                                $redirectStatus = constant(HttpUtility::class .'::HTTP_STATUS_'.$redirectStatus);
                            }
							\Nawork\NaworkUri\Utility\GeneralUtility::sendRedirect($uri, $redirectStatus);
							exit;
                        }
                    } catch (UrlIsNotUniqueException $ex) {
						/* log unique failure to belog */
						\Nawork\NaworkUri\Utility\GeneralUtility::log('Url "%s" is not unique with parameters %s. Referrer: %s', GeneralUtility::SYSLOG_SEVERITY_ERROR, array($ex->getPath(), \Nawork\NaworkUri\Utility\GeneralUtility::implode_parameters($ex->getParameters()), GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL')));
					} catch (DbErrorException $ex) {
						/* log db errors to belog */
						\Nawork\NaworkUri\Utility\GeneralUtility::log('An database error occured while creating a url. The SQL error was: "%s"', GeneralUtility::SYSLOG_SEVERITY_ERROR, array($ex->getSqlError()));
					} catch (TransformationServiceException $ex) {
					    \Nawork\NaworkUri\Utility\GeneralUtility::log('A transformation could not completed: ' . $ex->getMessage() . ($ex->getPrevious() instanceof \Exception ? ' The previous error was:' . $ex->getPrevious()->getMessage(): ''));
                    }
				}
			}
		}
	}

	/**
	 * Handles the pagenotfound event:
	 * This function is called from tx_naworkuri_uri::uri2params if the path is not found.
	 * Additionally it can be used as a user function in $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling'], e.g.:
	 * USER_FUNCTION:&Nawork\NaworkUri\Controller\Frontend\UrlController->handlePageNotFound.
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
	public function handlePageNotFound($params, $frontendController) {
	    $configuration = ConfigurationUtility::getConfiguration();
		if ($configuration instanceof Configuration) {
			$output = '';
            $disableOutput = FALSE;
			/* the page is not accessible without being logged in, so handle this, if configured */
			if (array_key_exists('pageAccessFailureReasons', $params) && is_array($params['pageAccessFailureReasons']) && array_key_exists('fe_group', $params['pageAccessFailureReasons']) && $configuration->getPageNotAccessibleConfiguration() instanceof PageNotAccessibleConfiguration) {
				$pageNotAccessibleConfiguration = $configuration->getPageNotAccessibleConfiguration();
				header($pageNotAccessibleConfiguration->getStatus());
				header('Content-type: text/html; charset=utf8');
				switch ($pageNotAccessibleConfiguration->getBehavior()) {
					case PageNotAccessibleConfiguration::BEHAVIOR_MESSAGE:
						$output = $pageNotAccessibleConfiguration->getValue();
						break;
					case PageNotAccessibleConfiguration::BEHAVIOR_PAGE:
					    if (!$this->pageNotAccessibleHandlingInProgress) {
					        $this->pageNotAccessibleHandlingInProgress = true; // avoid loops
                            if (MathUtility::canBeInterpretedAsInteger($pageNotAccessibleConfiguration->getValue()) || MathUtility::canBeInterpretedAsInteger(\Nawork\NaworkUri\Utility\GeneralUtility::aliasToId($pageNotAccessibleConfiguration->getValue()))) {
                                // the TSFE called the page not found handling, so we build a new request
                                GeneralUtility::_GETset($pageNotAccessibleConfiguration->getValue(), 'id');
                                $request = ServerRequestFactory::fromGlobals();
                                $bootstrap = Bootstrap::getInstance();
                                $requestHandler = new RequestHandler($bootstrap);
                                $response = $requestHandler->handleRequest($request);
                                $output = $response->getBody()->__toString();
                            } elseif (GeneralUtility::getIndpEnv('HTTP_USER_AGENT') != 'nawork_uri') {
                                // we have a url as the page not found config value AND the user agent is not nawork_uri (avoid loops)
                                $urlParts = parse_url($pageNotAccessibleConfiguration->getValue());
                                $urlParts['scheme'] = GeneralUtility::getIndpEnv('TYPO3_SSL') ? 'https' : 'http';
                                $notFoundUrl = $urlParts['scheme'] . '://' . $urlParts['host'] . $urlParts['path'] . (!empty($urlParts['query']) ? '?' . $urlParts['query'] : '');
                                $output = \Nawork\NaworkUri\Utility\HttpUtility::getUrlByCurl($notFoundUrl);
                            }
                        }
						break;
					case PageNotAccessibleConfiguration::BEHAVIOR_REDIRECT:
						$path = html_entity_decode($pageNotAccessibleConfiguration->getValue());
						if (!($_SERVER['REQUEST_METHOD'] == 'POST' && preg_match('/index.php/', $_SERVER['SCRIPT_NAME']))) {
							\Nawork\NaworkUri\Utility\GeneralUtility::sendRedirect($path, HttpUtility::HTTP_STATUS_301); // send headers and exit
						}
						break;
				}
				if (empty($output)) {
                    $output = '<html><head><title>403 Forbidden</title></head><body><h1>403 Forbidden</h1><p>You don\'t have the permission to access this page</p></body></html>';
                }
			} elseif ($configuration->getPageNotFoundConfiguration() instanceof PageNotFoundConfiguration) {
				$pageNotFoundConfiguration = $configuration->getPageNotFoundConfiguration();
				header('Content-Type: text/html; charset=utf-8');
				header($pageNotFoundConfiguration->getStatus());
				switch ($pageNotFoundConfiguration->getBehavior()) {
					case PageNotFoundConfiguration::BEHAVIOR_MESSAGE:
						$output = $pageNotFoundConfiguration->getValue();
						break;
					case PageNotFoundConfiguration::BEHAVIOR_PAGE:
					    if (!$this->pageNotFoundHandlingInProgress) {
					        $this->pageNotFoundHandlingInProgress = TRUE;
                            if (MathUtility::canBeInterpretedAsInteger($pageNotFoundConfiguration->getValue()) || MathUtility::canBeInterpretedAsInteger(\Nawork\NaworkUri\Utility\GeneralUtility::aliasToId($pageNotFoundConfiguration->getValue()))) {
                                // we have an id/alias as page not found config value
                                if (is_array($params['pageAccessFailureReasons']) && !array_key_exists('nawork_uri', $params['pageAccessFailureReasons'])) {
                                    // the TSFE called the page not found handling, so we build a new request
                                    GeneralUtility::_GETset($pageNotFoundConfiguration->getValue(), 'id');
                                    $signalSlotDispatcher = GeneralUtility::makeInstance(Dispatcher::class);
                                    $signalSlotDispatcher->dispatch(UrlController::class, 'beforeInternal404Request', ['params' => $params]);
                                    $request = ServerRequestFactory::fromGlobals();
                                    $bootstrap = Bootstrap::getInstance();
                                    $requestHandler = new RequestHandler($bootstrap);
                                    $response = $requestHandler->handleRequest($request);
                                    $output = $response->getBody()->__toString();
                                } else {
                                    // the page not found handling is called by nawork-uri after request to unknown url/path
                                    $frontendController->id = $pageNotFoundConfiguration->getValue();
                                    $disableOutput = TRUE; // let the frontend render normally
                                    $signalSlotDispatcher = GeneralUtility::makeInstance(Dispatcher::class);
                                    $signalSlotDispatcher->dispatch(UrlController::class, 'afterSetting404PageId', ['params' => $params]);
                                }
                            } elseif (GeneralUtility::getIndpEnv('HTTP_USER_AGENT') != 'nawork_uri') {
                                // we have a url as the page not found config value AND the user agent is not nawork_uri (avoid loops)
                                $urlParts = parse_url($pageNotFoundConfiguration->getValue());
                                $urlParts['scheme'] = GeneralUtility::getIndpEnv('TYPO3_SSL') ? 'https' : 'http';
                                $signalSlotDispatcher = GeneralUtility::makeInstance(Dispatcher::class);
                                $signalSlotDispatcher->dispatch(UrlController::class, 'beforeBuildingNotFoundUrl', ['urlParts' => &$urlParts]);
                                $notFoundUrl = $urlParts['scheme'] . '://' . $urlParts['host'] . $urlParts['path'] . (!empty($urlParts['query']) ? '?' . $urlParts['query'] : '');
                                $output = \Nawork\NaworkUri\Utility\HttpUtility::getUrlByCurl($notFoundUrl);
                            }
                        }
                        // no else here. If the content is empty, the default message will be shown.
						break;
					case PageNotFoundConfiguration::BEHAVIOR_REDIRECT:
						$path = html_entity_decode($pageNotFoundConfiguration->getValue());
						if (!($_SERVER['REQUEST_METHOD'] == 'POST' && preg_match('/index.php/', $_SERVER['SCRIPT_NAME']))) {
							\Nawork\NaworkUri\Utility\GeneralUtility::sendRedirect($path, HttpUtility::HTTP_STATUS_301); // send headers and exit
						}
						break;
					default:
						$output = '';
				}
			}
            if (!$disableOutput) {
                // if $output is still empty or false, output a 404 message
                if (empty($output)) {
                    $output = '<html><head><title>404 Not found</title></head><body><h1>Not found!</h1><p>The page you are trying to access is not available</p></body></html>';
                }
                echo $output;
                exit(0);
            }
		}
	}
}
