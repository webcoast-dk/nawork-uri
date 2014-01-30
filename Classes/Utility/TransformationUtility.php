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
	 * @var \Nawork\NaworkUri\Configuration\ConfigurationReader
	 */
	private $config;

	/**
	 *
	 * @var string
	 */
	private $domain;

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
	public function __construct($multidomain = false, $domain = '') {
		$this->db = $GLOBALS['TYPO3_DB'];
		// read configuration
		$this->config = ConfigurationUtility::getConfigurationReader();
		// get the domain, if multiple domain is not enabled the helper return ""
		$this->domain = $domain;
		if (empty($this->domain)) {
			$this->domain = GeneralUtility::getCurrentDomain();
		}
		$this->cache = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Nawork\NaworkUri\Cache\UrlCache');
		$this->cache->setTimeout(30);
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
		list($path, $params) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('?', $uri);
		// save the original path to check that if the "slashed" one does not return anything
		$path = urldecode($path);
		// look into the db
		$cache = $this->cache->read_path($path, $this->domain);
		if ($cache === FALSE) {
			/* if we have not found an entry, try to append a "/" an try again */
			if (!empty($path) && $append == '/' && substr($path, -strlen($append)) != $append && !preg_match('/\.\w{3,5}\d?$/', $path)) {
				$path .= (string) $this->config->getAppend();
			}
			$cache = $this->cache->read_path($path, $this->domain);
		}
		if ($cache) {
			if ($cache['type'] > 0) {
				throw new Tx_NaworkUri_Exception_UrlIsRedirectException($cache);
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
		if ($this->config->getCastTypeToInt()) {
			$type = !empty($params['type']) ? $params['type'] : \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('type');
			if (!empty($type) && !GeneralUtility::canBeInterpretedAsInteger($type)) { // if type is not an int
				unset($params['type']); // remove type param to use systems default
			}
		}

		/* check if L should be casted to int to avoid strange behavior when creating links */
		if ($this->config->getCastLToInt()) {
			$L = !empty($params['L']) ? $params['L'] : \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('L');
			if (!empty($L) && !GeneralUtility::canBeInterpretedAsInteger($L)) { // if L is not an int
				unset($params['L']); // remove L param to use system default
			}
		}
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
		$cachedUri = $this->cache->findCachedUrl($encodableParameters, $this->domain, $this->language);
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
			$append = $this->config->getAppend();
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

	/**
	 * Take parameter array and transform it into an array of path elements.
	 * 
	 * @param array $parameters The original parameter array
	 * @return array The transformed values
	 * @throws Exception
	 */
	public function transformParametersToPath($parameters) {
		$pathElements = array();
		/* @var $parameterConfiguration \SimpleXMLElement */
		foreach ($this->config->getParameterConfigurations() as $parameterConfiguration) {
			$parameterName = (string) $parameterConfiguration->name;
			if (array_key_exists($parameterName, $parameters)) {
				$transformationType = (string) $parameterConfiguration->type;
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['transformationServices']) && array_key_exists($transformationType, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['transformationServices'])) {
					/* @var $transformationService \Nawork\NaworkUri\Service\TransformationServiceInterface */
					$transformationService = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['transformationServices'][$transformationType]);
					if (!is_object($transformationService)) {
						throw new \Nawork\NaworkUri\Exception\InvalidTransformationServiceException('The transformation service for type \'' . $transformationType . '\' could not be instatiated, the class was not found');
					}
					if (!$transformationService instanceof \Nawork\NaworkUri\Service\TransformationServiceInterface) {
						throw new \Nawork\NaworkUri\Exception\InvalidTransformationServiceException('The transformation service for type \'' . $transformationType . '\' must implement \'\\Nawork\\NaworkUri\\Service\\TransformationServiceInterface\'');
					}
					$pathElements[$parameterName] = $transformationService->transform($parameterConfiguration, $parameters[$parameterName], $this);
				} else {
					throw new \Nawork\NaworkUri\Exception\MissingTransformationServiceException('No transformation service for type \'' . $transformationType . '\' registered');
					/**
					 * @todo Improve handling of missing transformation service
					 */
				}
			}
		}
		return $pathElements;
	}

	public function getLanguage() {
		return $this->language;
	}

}

?>
