<?php

namespace Nawork\NaworkUri\Utility;

/**
 * Class for creating path uris
 *
 * @author Martin Ficzel
 *
 */
class TransformationUtility implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 *
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	private $db;

	/**
	 * @var \Nawork\NaworkUri\Cache\UrlCache
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
	 * @param \Nawork\NaworkUri\Configuration\ConfigurationReader $config
	 * @param boolean $multidomain
	 * @param string $domain
	 */
	public function __construct() {
		$this->db = $GLOBALS['TYPO3_DB'];
		// get the domain
		$this->domain = GeneralUtility::findCurrentDomain();
		$this->cache = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Nawork\NaworkUri\Cache\UrlCache');
		$this->cache->setTimeout(30);
	}

	/**
	 * @param string $domain
	 */
	public function setDomain($domain) {
		$this->domain = $domain;
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
		$append = ConfigurationUtility::getConfiguration()->getGeneralConfiguration()->getAppend();
		list($path, $params) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('?', $uri);
		// save the original path to check that if the "slashed" one does not return anything
		$path = urldecode($path);
		// look into the db
		$cache = $this->cache->read_path($path, $this->domain);
		if ($cache === FALSE) {
			/* if we have not found an entry, try to append a "/" an try again */
			if (!empty($path) && $append == '/' && substr($path, -strlen($append)) != $append && !preg_match('/\.\w{3,5}\d?$/', $path)) {
				$path .= $append;
			}
			$cache = $this->cache->read_path($path, $this->domain);
		}
		if ($cache) {
			if ($cache['type'] > 0) {
				throw new \Nawork\NaworkUri\Exception\UrlIsRedirectException($cache);
			}
			// cachedparams
			$cachedparams = Array();
			parse_str($cache['params'], $cachedparams);
			$cachedparams['id'] = $cache['page_uid'];
			$cachedparams['L'] = $cache['sys_language_uid'];
			// classic url params
			$getparams = Array();
			parse_str($params, $getparams);
			// merged result
			$res = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($cachedparams, $getparams);
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
		$params = GeneralUtility::explode_parameters($parameters);
		$orgParams = $params;
		/* add hook for processing the parameter set */
		if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['tx_naworkuri']['parameterSet-preProcess'])) {
			foreach ($TYPO3_CONF_VARS['SC_OPTIONS']['tx_naworkuri']['parameterSet-preProcess'] as $funcRef) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
			}
		}
		/* if something destroys the params reset them */
		if (!is_array($params) || !array_key_exists('id', $params)) {
			$params = $orgParams;
		}

		/* we must have an integer id so lets look it up */
		$params['id'] = GeneralUtility::aliasToId($params['id']);

		/* check if type should be casted to int to avoid strange behavior when creating links */
//		if ($this->config->getCastTypeToInt()) {
//			$type = !empty($params['type']) ? $params['type'] : \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('type');
//			if (!empty($type) && !GeneralUtility::canBeInterpretedAsInteger($type)) { // if type is not an int
//				unset($params['type']); // remove type param to use systems default
//			}
//		}

		/* check if L should be casted to int to avoid strange behavior when creating links */
//		if ($this->config->getCastLToInt()) {
//			$L = !empty($params['L']) ? $params['L'] : \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('L');
//			if (!empty($L) && !GeneralUtility::canBeInterpretedAsInteger($L)) { // if L is not an int
//				unset($params['L']); // remove L param to use system default
//			}
//		}
		if (!isset($params['L'])) {
			/* append an empty string to make sure this value is a string when given to \TYPO3\CMS\Core\Utility\GeneralUtility::calculateCHash */
			$params['L'] = '' . ($GLOBALS['TSFE']->sys_language_uid ? $GLOBALS['TSFE']->sys_language_uid : 0);
		}

		/* recalculate the cHash to avoid doublicated urls with different cHashes based on encoded or non-encoded parameters, e.g. from the crawler */
		if (isset($params['cHash'])) {
			$cHashParams = $params;
			unset($cHashParams['cHash']);
			ksort($cHashParams);
			/* @var $cHashCaluclator \TYPO3\CMS\Frontend\Page\CacheHashCalculator */
			$cHashCaluclator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Frontend\Page\CacheHashCalculator');
			$params['cHash'] = $cHashCaluclator->calculateCacheHash($cHashCaluclator->getRelevantParameters(GeneralUtility::implode_parameters($params, FALSE)));
		}

		$this->language = $params['L'];
		/* find cached urls with the given parameters from the current domain */
		list($encodableParameters, $unencodableParameters) = GeneralUtility::filterConfiguredParameters($params);
		$cachedUri = $this->cache->findCachedUrl($encodableParameters, $this->domain, $this->language, $ignoreTimeout);
		if ($cachedUri !== FALSE) {
			/* compute the unencoded parameters */
			if (count($unencodableParameters) > 0) {
				$cachedUri .= '?' . GeneralUtility::implode_parameters($unencodableParameters);
			}
			/* append the anchor if not empty */
			if ($anchor) {
				$cachedUri .= '#' . $anchor;
			}
			return $cachedUri;
		} elseif ($dontCreateNewUrls && $cachedUri === false) {
			return false;
		}

		// create new uri because no exact match was found in cache
		$pathElements = $this->transformParametersToPath($encodableParameters);
		$encodedParameters = array_intersect_key($encodableParameters, $pathElements);
		$unencodedParameters = array_diff_key($params, $pathElements);

		/*
		 * Check for any parameter that should be included in the cacheHash if it is was not encoded.
		 * If this is the case, remove the cHash parameter from the encoded parameters and append it.
		 * This avoids "-1" urls.
		 */
		if (count($unencodedParameters) > 0 && isset($pathElements['cHash'])) {
			$excludeCacheHash = FALSE;
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'])) {
				foreach (array_keys($unencodedParameters) as $parameterName) {
					if (!in_array($parameterName, $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'])) {
						$excludeCacheHash = TRUE;
					}
				}
			}
			if ($excludeCacheHash) {
				$unencodedParameters['cHash'] = $encodableParameters['cHash'];
				unset($pathElements['cHash']);
			}
		}

		// return
		if (count($pathElements)) {
			$encoded_uri = implode('/', $pathElements);
		} else {
			$encoded_uri = '';
		}

		$result_path = GeneralUtility::sanitize_uri($encoded_uri);

		// append
		if ($result_path) {
			$append = ConfigurationUtility::getConfiguration()->getGeneralConfiguration()->getAppend();
			if (substr($result_path, -strlen($append)) != $append) {
				$result_path = $result_path . $append;
			}
		}
		$uri = $this->cache->writeUrl($encodedParameters, $this->domain, $this->language, $result_path);

		// read not encoded parameters
		$i = 0;
		foreach ($unencodedParameters as $key => $value) {
			$uri.= ( ($i > 0) ? '&' : '?' ) . $key . '=' . $value;
			$i++;
		}

		// append stored anchor
		if ($anchor) {
			$uri .= '#' . $anchor;
		}

		return($uri);
	}

	public function transformParametersToPath($parameters) {
		$pathParts = array();
		foreach (ConfigurationUtility::getConfiguration()->getParametersConfiguration()
			->getParameterTransformationConfigurations() as $transformationConfiguration) {
			if (array_key_exists($transformationConfiguration->getName(), $parameters)) {
				try {
					if (self::isTransformationServiceRegistered($transformationConfiguration->getType())) {
						$transformationServiceClassName = self::getTransformationServiceClassName($transformationConfiguration->getType());
						if (class_exists($transformationServiceClassName)) {
							/* @var $transformationService \Nawork\NaworkUri\Transformation\AbstractTransformationService */
							$transformationService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($transformationServiceClassName);
							if (!$transformationService instanceof \Nawork\NaworkUri\Transformation\AbstractTransformationService) {
								throw new \Nawork\NaworkUri\Exception\TransformationServiceException($transformationConfiguration->getName(), $transformationConfiguration->getType(), $transformationServiceClassName . ' must extend Kapp\\UrlRewrite\\Transformation\\AbstractTransformationService');
							}
							try {
								$transformedValue = $transformationService->transform($transformationConfiguration,
									$parameters[$transformationConfiguration->getName()],
									$this);
								// only add non-empty values to the path to avoid "//" in the resulting path
//								if (!empty($transformedValue)) {
									$pathParts[$transformationConfiguration->getName()] = $transformedValue;
//								}
							} catch (\Nawork\NaworkUri\Exception\TransformationException $e) {
								throw new \Nawork\NaworkUri\Exception\TransformationServiceException($transformationConfiguration->getName(), $transformationConfiguration->getType(), 'An exception was thrown while transforming the value. The message was: ' . $e->getMessage(), $e);
							}
						} else {
							throw new \Nawork\NaworkUri\Exception\TransformationServiceException($transformationConfiguration->getName(), $transformationConfiguration->getType(), 'The transformation service class "' . $transformationServiceClassName . '" was not found, please check, if it exists');
						}
					} else {
						throw new \Nawork\NaworkUri\Exception\TransformationServiceException($transformationConfiguration->getName(), $transformationConfiguration->getType(), 'No transformation service for type "' . $transformationConfiguration->getType() . '" registered');
					}
				} catch (\Exception $ex) {
					/**
					 * @todo Do some logging here
					 */
				}
			}
		}
		return $pathParts;
	}

	public function getLanguage() {
		return $this->language;
	}

	/**
	 * Register transformation services.
	 *
	 * @param string $type           The name of the transformation type as used in the configuration, e.g. "ValueMap"
	 * @param string $classReference The class reference to the transformation service, e.g. "EXT:myext/Classes/Service/MyTransformationService.php:My\MyExt\Service\MyTransformationService
	 */
	public static function registerTransformationService($type, $classReference) {
		if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['TransformationServices'])) {
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['TransformationServices'] = array();
		}
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['TransformationServices'][$type] = $classReference;
	}

	/**
	 * Check if a transformation service for the given type is registered
	 *
	 * @param string $type The type as used in the configuration file, e.g. "Hidden"
	 *
	 * @return bool Returns true, if there is a transformation service registered for the given type, false otherwise
	 */
	public static function isTransformationServiceRegistered($type) {
		return array_key_exists($type, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['TransformationServices']);
	}

	/**
	 * Get the class name of the transformation service for the given type.
	 * !!!Important: Check if it is registered first to avoid errors for non existing array key
	 *
	 * @param string $type The type as used in the configuration file, e.g. "Hidden"
	 *
	 * @return string Returns the namespaced class name as it was registered before
	 */
	public static function getTransformationServiceClassName($type) {
		return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['TransformationServices'][$type];
	}

}

?>
