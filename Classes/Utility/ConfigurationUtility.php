<?php

namespace Nawork\NaworkUri\Utility;

use Nawork\NaworkUri\Exception\InvalidConfigurationException;

class ConfigurationUtility {
	/**
	 * @var \Nawork\NaworkUri\Configuration\ConfigurationReader
	 */
	private static $configurationReader;

	/**
	 * @var \Nawork\NaworkUri\Configuration\Configuration
	 */
	protected static $configuration;

	/**
	 * @var \Nawork\NaworkUri\Configuration\Configuration[]
	 */
	protected static $configurations = array();

	protected static $inheritanceLock = array();

	public static function getConfigurationFileForCurrentDomain() {
		/** @var \Nawork\NaworkUri\Configuration\TableConfiguration $tableConfiguration */
		$tableConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Nawork\\NaworkUri\\Configuration\\TableConfiguration');
		$file = NULL;
		try {
			// try to find the configuration for the current host name, e.g. for
			// local development or testing environment: this ignores master domains
			$file = self::findConfigurationByDomain(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY'));
		} catch (InvalidConfigurationException $ex) {
			$domain = 'default';
			// look, if there is a domain record matching the current hostname,
			// this includes recursive look up of master domain records
			$domainUid = GeneralUtility::getCurrentDomain();
			if ($domainUid > 0) {
				$domainRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('domainName', $tableConfiguration->getDomainTable(), 'uid=' . intval($domainUid));
				if (is_array($domainRecord)) {
					$domain = $domainRecord['domainName'];
				}
			}
			try {
				// look for a configuration file for the evaluated domain
				$file = self::findConfigurationByDomain($domain);
			} catch (InvalidConfigurationException $ex) {
				try {
					// as a fallback use the default configuration
					$file = self::findConfigurationByDomain('default');
				} catch (InvalidConfigurationException $ex) {
				}
			}

		}

		return $file;
	}

	private static function findConfigurationByDomain($domain) {
		if (!array_key_exists($domain, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['configurations'])) {
			throw new InvalidConfigurationException('No configuration for domain \'' . $domain . '\' registered', 1391077835);
		}

		$file = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['configurations'][$domain], TRUE, TRUE);
		if (!file_exists($file) || !is_file($file) && !is_link($file)) {
			throw new InvalidConfigurationException('The configuration file for domain \'' . $domain . '\' does not exist or is not a file/link', 1391077846);
		}

		return $file;
	}

	/**
	 * @return \Nawork\NaworkUri\Configuration\ConfigurationReader
	 */
	public static function getConfigurationReader() {
		if (!self::$configurationReader instanceof \Nawork\NaworkUri\Configuration\ConfigurationReader) {
			self::$configurationReader = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Nawork\\NaworkUri\\Configuration\\ConfigurationReader', self::getConfigurationFileForCurrentDomain());
		}

		return self::$configurationReader;
	}

	/**
	 * @param string $domain           The domain name or keyword "default"
	 * @param string $path             The path to the configuration file, e.g. EXT:my_ext/Configuration/Url/Default.xml
	 * @param bool   $overrideExisting Set to false if you do not want to override an existing configuration for this domain
	 */
	public static function registerConfiguration($domain, $path, $overrideExisting = TRUE) {
		if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['Configurations'])) {
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['Configurations'] = array();
		}
		$absolutePath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($path);
		if ($overrideExisting || !array_key_exists($domain,
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['Configurations'])
		) {
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['Configurations'][$domain] = $absolutePath;
		}
	}

	/**
	 * The public method that is to be used to get the configuration object
	 *
	 * @params string $domain
	 *
	 * @return \Nawork\NaworkUri\Configuration\Configuration
	 */
	public static function getConfiguration($domain = NULL) {
		if ($domain !== NULL || !self::$configuration instanceof \Nawork\NaworkUri\Configuration\Configuration) {
			self::$configuration = self::getConfigurationObject($domain);
			if($domain !== NULL) {
				self::$configurations[$domain] = self::$configuration;
			}
		}
		return self::$configuration;
	}

	/**
	 * Try to find a configuration object by trying the following options:
	 *
	 * 1. current host name
	 * 2. determined master domain
	 * 3. default configuration
	 *
	 * @params string $domain
	 *
	 * @return \Nawork\NaworkUri\Configuration\Configuration
	 *
	 * @throws \Nawork\NaworkUri\Exception\InheritanceException
	 */
	private static function getConfigurationObject($domain = NULL) {
		try {
			return self::getConfigurationObjectForDomain($domain);
		} catch(\Exception $ex) {
			// inheritance exceptions must bubble up
			if ($ex instanceof \Nawork\NaworkUri\Exception\InheritanceException) {
				throw $ex;
			}
			try {
				return self::getConfigurationObjectForDomain(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY'));
			} catch (\Exception $ex) {
				// inheritance exceptions must bubble up
				if ($ex instanceof \Nawork\NaworkUri\Exception\InheritanceException) {
					throw $ex;
				}
				try {
					return self::getConfigurationObjectForDomain(GeneralUtility::getCurrentDomainName());
				} catch (\Exception $ex) {
					// inheritance exceptions must bubble up
					if ($ex instanceof \Nawork\NaworkUri\Exception\InheritanceException) {
						throw $ex;
					}
					return self::getConfigurationObjectForDomain('default');
				}
			}
		}
	}

	/**
	 * Check if a configuration was registered for the given domain. If yes, try to load it from the compiled
	 * configuration files. If that fails, build the configuration from the xml
	 *
	 * @param string $domain
	 *
	 * @return \Nawork\NaworkUri\Configuration\Configuration
	 *
	 * @throws \Exception
	 */
	private static function getConfigurationObjectForDomain($domain) {
		if(array_key_exists($domain, self::$configurations)) {
			return self::$configurations[$domain];
		}
		try {
			return self::readCompiledConfigurationFile($domain);
		} catch (\Exception $e) {
			if (array_key_exists($domain, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['Configurations'])) {
				$configuration = self::buildConfigurationForDomain($domain);
				self::storeCompiledConfigurationToFile($configuration,
					\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY'));
				return $configuration;
			}
			throw new \Exception('No configuration is registered for domain "' . $domain . '"', 1394135040);
		}
	}

	private static function storeCompiledConfigurationToFile($configuration, $domain) {
		/* @var $extensionConfiguration \Nawork\NaworkUri\Configuration\ExtensionConfiguration */
		$extensionConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Nawork\\NaworkUri\\Configuration\\ExtensionConfiguration');
		$file = $extensionConfiguration->getConfigurationCacheDirectory() . $domain;
		file_put_contents($file, serialize($configuration));
	}

	/**
	 * Read and unserialize the configuration file, if it exists. Throw an exception if not.
	 *
	 * @param string $domain
	 *
	 * @return \Nawork\NaworkUri\Configuration\Configuration
	 *
	 * @throws \Exception
	 */
	private static function readCompiledConfigurationFile($domain) {
		/* @var $extensionConfiguration \Nawork\NaworkUri\Configuration\ExtensionConfiguration */
		$extensionConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Nawork\\NaworkUri\\Configuration\\ExtensionConfiguration');
		$file = $extensionConfiguration->getConfigurationCacheDirectory() . $domain;
		if (!file_exists($file) || !is_file($file)) {
			throw new \Exception('The configuration file "' . $file . '" does not exist', 1394131984);
		}
		return unserialize(file_get_contents($file));
	}

	/**
	 * Determine the xml configuration file from registered configurations and put it
	 * into a SimpleXMLElement
	 *
	 * @param string $domain
	 *
	 * @return \SimpleXMLElement
	 *
	 * @throws \Exception
	 */
	private static function readXmlConfigurationFile($domain) {
		if (!array_key_exists($domain, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['Configurations'])) {
			throw new \Exception('No configuration registered for domain "' . $domain . '"', 1394137785);
		}
		$file = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['Configurations'][$domain]);
		if (file_exists($file) && is_readable($file)) {
			return new \SimpleXMLElement($file, 0, TRUE);
		}
		throw new \Exception('The xml configuration file "' . $file . '" does not exist, or is not readable', 1394137986);
	}

	/**
	 * Read xml configuration file and build a configuration object out of it
	 *
	 * @param string $domain
	 *
	 * @return \Nawork\NaworkUri\Configuration\Configuration
	 *
	 * @throws \Nawork\NaworkUri\Exception\InheritanceException
	 */
	private static function buildConfigurationForDomain($domain) {
		$xml = self::readXmlConfigurationFile($domain);
		$configuration = NULL;
		if ($xml->attributes()->extends && strcmp('', (string)$xml->attributes()->extends)) {
			$extendedDomainName = (string)$xml->attributes()->extends;
			// the domain is already used for extending this type of configuration, throw an exception
			if (self::$inheritanceLock[$extendedDomainName]) {
				throw new \Nawork\NaworkUri\Exception\InheritanceException('UrlConfiguration', $extendedDomainName);
			}
			try {
				self::$inheritanceLock[$extendedDomainName] = TRUE;
				$configuration = self::getConfigurationObjectForDomain((string)$xml->attributes()->extends);
			} catch (\Exception $e) {
				// inheritance exceptions must bubble up
				if ($e instanceof \Nawork\NaworkUri\Exception\InheritanceException) {
					throw $e;
				}
				self::$inheritanceLock[$extendedDomainName] = FALSE;
			}
		}
		if ($configuration === NULL) {
			$configuration = new \Nawork\NaworkUri\Configuration\Configuration();
		}

		$configuration->setGeneralConfiguration(self::buildGeneralConfiguration($xml->General,
				$configuration->getGeneralConfiguration()));
		if (!$configuration->getGeneralConfiguration()->getDisabled()) {
			$configuration->setTransliterationsConfiguration(self::buildTransliterationsConfiguration($xml->Transliterations,
					$configuration->getTransliterationsConfiguration()));
			$configuration->setPageNotFoundConfiguration(self::buildPageNotFoundConfiguration($xml->PageNotFound,
					$configuration->getPageNotFoundConfiguration()));
			$configuration->setPageNotAccessibleConfiguration(self::buildPageNotAccessibleConfiguration($xml->PageNotAccessible,
					$configuration->getPageNotAccessibleConfiguration()));
			$configuration->setPageNotTranslatedConfiguration(self::buildPageNotTranslatedConfiguration($xml->PageNotTranslated,
					$configuration->getPageNotTranslatedConfiguration()));
			$configuration->setParametersConfiguration(self::buildParametersConfiguration($xml->Parameters,
					$configuration->getParametersConfiguration()));
		}

		return $configuration;
	}

	/**
	 * @param \SimpleXMLElement                                   $xml
	 * @param \Nawork\NaworkUri\Configuration\GeneralConfiguration $configuration |NULL
	 *
	 * @return \Nawork\NaworkUri\Configuration\GeneralConfiguration
	 *
	 * @throws \Nawork\NaworkUri\Exception\InheritanceException
	 */
	private static function buildGeneralConfiguration($xml, $configuration) {
		// if there is an extends set it overrides already given $configuration
		if ($xml && $xml->attributes()->extends && strcmp('', (string)$xml->attributes()->extends)) {
			$extendedDomainName = (string)$xml->attributes()->extends;
			// the domain is already used for extending this type of configuration, throw an exception
			if (self::$inheritanceLock[$extendedDomainName . '.General']) {
				throw new \Nawork\NaworkUri\Exception\InheritanceException('GeneralConfiguration', $extendedDomainName);
			}
			try {
				self::$inheritanceLock[$extendedDomainName . '.General'] = TRUE;
				$configuration = self::getConfigurationObjectForDomain((string)$xml->attributes()->extends)
					->getGeneralConfiguration();
			} catch (\Exception $e) {
				if ($e instanceof \Nawork\NaworkUri\Exception\InheritanceException) {
					throw $e;
				}
				$configuration = NULL;
				self::$inheritanceLock[$extendedDomainName . '.General'] = FALSE;
			}
		}
		if ($configuration === NULL) {
			$configuration = new \Nawork\NaworkUri\Configuration\GeneralConfiguration();
		}
		if ($xml->Append) {
			$configuration->setAppend((string)$xml->Append);
		}
		$configuration->setDisabled((bool)(int)$xml->Disabled);
		if ($xml->PathSeparator) {
			$configuration->setPathSeparator((string)$xml->PathSeparator);
		}
		if($xml->RedirectStatus) {
			$configuration->setRedirectStatus((string)$xml->RedirectStatus);
		}
		return $configuration;
	}

	/**
	 * @param $xml           \SimpleXMLElement
	 * @param $configuration \Nawork\NaworkUri\Configuration\TransliterationsConfiguration
	 *
	 * @throws \Nawork\NaworkUri\Exception\InheritanceException
	 */
	private static function buildTransliterationsConfiguration($xml, $configuration) {
		// if there is an extends set it overrides already given $configuration
		if ($xml && $xml->attributes()->extends && strcmp('', (string)$xml->attributes()->extends)) {
			$extendedDomainName = (string)$xml->attributes()->extends;
			// the domain is already used for extending this type of configuration, throw an exception
			if (self::$inheritanceLock[$extendedDomainName . '.Transliterations']) {
				throw new \Nawork\NaworkUri\Exception\InheritanceException('TransliterationsConfiguration', $extendedDomainName);
			}
			try {
				self::$inheritanceLock[$extendedDomainName . '.Transliterations'] = TRUE;
				$configuration = self::getConfigurationObjectForDomain((string)$xml->attributes()->extends)
					->getTransliterationsConfiguration();
			} catch (\Exception $e) {
				if ($e instanceof \Nawork\NaworkUri\Exception\InheritanceException) {
					throw $e;
				}
				$configuration = NULL;
				self::$inheritanceLock[$extendedDomainName . '.Transliterations'] = FALSE;
			}
		}
		if ($configuration === NULL) {
			$configuration = new \Nawork\NaworkUri\Configuration\TransliterationsConfiguration();
		}
		if ($xml) {
			/* @var $character \SimpleXMLElement */
			foreach ($xml->children() as $character) {
				if ($character->getName() == 'Character') {
					$configuration->addCharacter((string)$character->attributes()->From,
						(string)$character->attributes()->To);
				}
			}
		}
		return $configuration;
	}

	private static function buildPageNotFoundConfiguration($xml, $configuration) {
		// if there is an extends set it overrides already given $configuration
		if ($xml && $xml->attributes()->extends && strcmp('', (string)$xml->attributes()->extends)) {
			$extendedDomainName = (string)$xml->attributes()->extends;
			// the domain is already used for extending this type of configuration, throw an exception
			if (self::$inheritanceLock[$extendedDomainName . '.PageNotFound']) {
				throw new \Nawork\NaworkUri\Exception\InheritanceException('PageNotFoundConfiguration', $extendedDomainName);
			}
			try {
				self::$inheritanceLock[$extendedDomainName . '.PageNotFound'] = TRUE;
				$configuration = self::getConfigurationObjectForDomain((string)$xml->attributes()->extends)
					->getPageNotFoundConfiguration();
			} catch (\Exception $e) {
				if ($e instanceof \Nawork\NaworkUri\Exception\InheritanceException) {
					throw $e;
				}
				$configuration = NULL;
				self::$inheritanceLock[$extendedDomainName . '.PageNotFound'] = FALSE;
			}
		}
		if ($configuration === NULL) {
			$configuration = new \Nawork\NaworkUri\Configuration\PageNotFoundConfiguration();
		}
		if ($xml->Behavior && strcmp('', (string)$xml->Behavior)) {
			switch ((string)$xml->Behavior) {
				case 'Page':
					$configuration->setBehavior(\Nawork\NaworkUri\Configuration\PageNotFoundConfiguration::BEHAVIOR_PAGE);
					break;
				case 'Redirect':
					$configuration->setBehavior(\Nawork\NaworkUri\Configuration\PageNotFoundConfiguration::BEHAVIOR_REDIRECT);
					break;
				case 'Message':
				default:
					$configuration->setBehavior(\Nawork\NaworkUri\Configuration\PageNotFoundConfiguration::BEHAVIOR_MESSAGE);
			}
		}
		if ($xml->Status && strcmp('', (string)$xml->Status)) {
			$configuration->setStatus((string)$xml->Status);
		}
		if ($xml->Value && strcmp('', (string)$xml->Value)) {
			$configuration->setValue((string)$xml->Value);
		}

		return $configuration;
	}

	private static function buildPageNotAccessibleConfiguration($xml, $configuration) {
		// if there is an extends set it overrides already given $configuration
		if ($xml && $xml->attributes()->extends && strcmp('', (string)$xml->attributes()->extends)) {
			$extendedDomainName = (string)$xml->attributes()->extends;
			// the domain is already used for extending this type of configuration, throw an exception
			if (self::$inheritanceLock[$extendedDomainName . '.PageNotAccessible']) {
				throw new \Nawork\NaworkUri\Exception\InheritanceException('PageNotAccessibleConfiguration', $extendedDomainName);
			}
			try {
				self::$inheritanceLock[$extendedDomainName . '.PageNotAccessible'] = TRUE;
				$configuration = self::getConfigurationObjectForDomain((string)$xml->attributes()->extends)
					->getPageNotAccessibleConfiguration();
			} catch (\Exception $e) {
				if ($e instanceof \Nawork\NaworkUri\Exception\InheritanceException) {
					throw $e;
				}
				$configuration = NULL;
				self::$inheritanceLock[$extendedDomainName . '.PageNotAccessible'] = FALSE;
			}
		}
		if ($configuration === NULL) {
			$configuration = new \Nawork\NaworkUri\Configuration\PageNotAccessibleConfiguration();
		}
		if ($xml->Behavior && strcmp('', (string)$xml->Behavior)) {
			switch ((string)$xml->Behavior) {
				case 'Page':
					$configuration->setBehavior(\Nawork\NaworkUri\Configuration\PageNotAccessibleConfiguration::BEHAVIOR_PAGE);
					break;
				case 'Redirect':
					$configuration->setBehavior(\Nawork\NaworkUri\Configuration\PageNotAccessibleConfiguration::BEHAVIOR_REDIRECT);
					break;
				case 'Message':
				default:
					$configuration->setBehavior(\Nawork\NaworkUri\Configuration\PageNotAccessibleConfiguration::BEHAVIOR_MESSAGE);
			}
		}
		if ($xml->Status && strcmp('', (string)$xml->Status)) {
			$configuration->setStatus((string)$xml->Status);
		}
		if ($xml->Value && strcmp('', (string)$xml->Value)) {
			$configuration->setValue((string)$xml->Value);
		}


		return $configuration;
	}

	private static function buildPageNotTranslatedConfiguration($xml, $configuration) {
		// if there is an extends set it overrides already given $configuration
		if ($xml && $xml->attributes()->extends && strcmp('', (string)$xml->attributes()->extends)) {
			$extendedDomainName = (string)$xml->attributes()->extends;
			// the domain is already used for extending this type of configuration, throw an exception
			if (self::$inheritanceLock[$extendedDomainName . '.PageNotTranslated']) {
				throw new \Nawork\NaworkUri\Exception\InheritanceException('PageNotTranslatedConfiguration', $extendedDomainName);
			}
			try {
				self::$inheritanceLock[$extendedDomainName . '.PageNotTranslated'] = TRUE;
				$configuration = self::getConfigurationObjectForDomain((string)$xml->attributes()->extends)
					->getPageNotTranslatedConfiguration();
			} catch (\Exception $e) {
				if ($e instanceof \Nawork\NaworkUri\Exception\InheritanceException) {
					throw $e;
				}
				$configuration = NULL;
				self::$inheritanceLock[$extendedDomainName . '.PageNotTranslated'] = FALSE;
			}
		}
		if ($configuration === NULL) {
			$configuration = new \Nawork\NaworkUri\Configuration\PageNotTranslatedConfiguration();
		}
		if ($xml->Behavior && strcmp('', (string)$xml->Behavior)) {
			switch ((string)$xml->Behavior) {
				case 'Page':
					$configuration->setBehavior(\Nawork\NaworkUri\Configuration\PageNotTranslatedConfiguration::BEHAVIOR_PAGE);
					break;
				case 'Redirect':
					$configuration->setBehavior(\Nawork\NaworkUri\Configuration\PageNotTranslatedConfiguration::BEHAVIOR_REDIRECT);
					break;
				case 'Message':
				default:
					$configuration->setBehavior(\Nawork\NaworkUri\Configuration\PageNotTranslatedConfiguration::BEHAVIOR_MESSAGE);
			}
		}
		if ($xml->Status && strcmp('', (string)$xml->Status)) {
			$configuration->setStatus((string)$xml->Status);
		}
		if ($xml->Value && strcmp('', (string)$xml->Value)) {
			$configuration->setValue((string)$xml->Value);
		}


		return $configuration;
	}

	/**
	 * @param \SimpleXMLElement                                      $xml
	 * @param \Nawork\NaworkUri\Configuration\ParametersConfiguration $configuration
	 *
	 * @return \Nawork\NaworkUri\Configuration\ParametersConfiguration
	 *
	 * @throws \Nawork\NaworkUri\Exception\InheritanceException
	 */
	private static function buildParametersConfiguration($xml, $configuration) {
		// if there is an extends set it overrides already given $configuration
		if ($xml && $xml->attributes()->extends && strcmp('', (string)$xml->attributes()->extends)) {
			$extendedDomainName = (string)$xml->attributes()->extends;
			// the domain is already used for extending this type of configuration, throw an exception
			if (self::$inheritanceLock[$extendedDomainName . '.Parameters']) {
				throw new \Nawork\NaworkUri\Exception\InheritanceException('ParametersConfiguration', $extendedDomainName);
			}
			try {
				self::$inheritanceLock[$extendedDomainName . '.Parameters'] = TRUE;
				$configuration = self::getConfigurationObjectForDomain((string)$xml->attributes()->extends)
					->getParametersConfiguration();
			} catch (\Exception $e) {
				if ($e instanceof \Nawork\NaworkUri\Exception\InheritanceException) {
					throw $e;
				}
				$configuration = NULL;
				self::$inheritanceLock[$extendedDomainName . '.Parameters'] = FALSE;
			}
		}
		if ($configuration === NULL) {
			$configuration = new \Nawork\NaworkUri\Configuration\ParametersConfiguration();
		}
		if ($xml) {

			/* @var $transformationConfigurationXml \SimpleXMLElement */
			foreach ($xml->children() as $transformationConfigurationXml) {
				if ($transformationConfigurationXml->getName() == 'TransformationConfiguration') {
					$parameterName = (string)$transformationConfigurationXml->Name;
					if ($transformationConfigurationXml->attributes()->extends && strcmp('',
							(string)$transformationConfigurationXml->attributes()->extends)
					) {
						$extendedDomainName = (string)$transformationConfigurationXml->attributes()->extends;
						// the domain is already used for extending this type of configuration, throw an exception
						if (self::$inheritanceLock[$extendedDomainName . '.Parameters.' . $parameterName]) {
							throw new \Nawork\NaworkUri\Exception\InheritanceException('TransformationConfiguration:' . $parameterName, $extendedDomainName);
						}
						try {
							self::$inheritanceLock[$extendedDomainName . '.Parameters.' . $parameterName] = TRUE;
							$transformationConfiguration = self::getConfigurationObjectForDomain((string)$transformationConfigurationXml->attributes()->extends)
								->getParametersConfiguration()
								->getParameterTransformationConfigurationByName($parameterName);
						} catch (\Exception $e) {
							if ($e instanceof \Nawork\NaworkUri\Exception\InheritanceException) {
								throw $e;
							}
							$transformationConfiguration = NULL;
							self::$inheritanceLock[$extendedDomainName . '.Parameters.' . $parameterName] = FALSE;
						}
					} else {
						$transformationConfiguration = NULL;
					}
					if ($transformationConfiguration === NULL) {
						$type = (string)$transformationConfigurationXml->Type;
					} else {
						$type = $transformationConfiguration->getType();
					}
					if (TransformationUtility::isTransformationServiceRegistered($type)) {
						$transformationServiceClassName = TransformationUtility::getTransformationServiceClassName($type);
						$classNameParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('\\',
							$transformationServiceClassName);
						// build configuration class name
						array_pop($classNameParts);
						array_push($classNameParts, 'TransformationConfiguration');
						$transformationConfigurationClassName = implode('\\', $classNameParts);
						// if the configuration class does not exist - and can not be loaded - log this and continue
						// this leaves this parameter out of the configuration
						if (!class_exists($transformationConfigurationClassName)) {
							/**
							 * @todo Log this somewhere
							 */
							continue;
						}
						/* @var $transformationConfiguration \Nawork\NaworkUri\Transformation\AbstractTransformationConfiguration */
						$transformationConfiguration = new $transformationConfigurationClassName();
						$transformationConfiguration->setName((string)$transformationConfigurationXml->Name);
						// build the configuration based in the given properties
						foreach($transformationConfiguration->getAdditionalProperties() as $name => $type) {
							// exclude name and type values as there are set already
							if(!in_array($name, array('Name', 'Type'))) {
								$setFunctionName = 'set'.ucfirst($name);
								if($transformationConfigurationXml->$name && $transformationConfigurationXml->$name instanceof \SimpleXMLElement && method_exists($transformationConfiguration, $setFunctionName)) {
									$value = NULL;
									switch ($type) {
										case 'bool':
										case 'boolean':
											$value = (bool) (int) $transformationConfigurationXml->$name;
											break;
										case 'int':
										case 'integer':
											$value = (int) $transformationConfigurationXml->$name;
											break;
										case 'string':
											$value = (string) $transformationConfigurationXml->$name;
									}
									$transformationConfiguration->$setFunctionName($value);
								}
							}
						}
						// try to find an additional configuration reader
						array_pop($classNameParts);
						array_push($classNameParts, 'ConfigurationReader');
						$configurationReaderClassName = implode('\\', $classNameParts);
						// if the class exists create an instance and let it change the
						// given transformation configuration
						if (class_exists($configurationReaderClassName)) {
							$configurationReader = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
								$configurationReaderClassName
							);
							if ($configurationReader
								instanceof
								\Nawork\NaworkUri\Transformation\AbstractConfigurationReader
							) {
								$configurationReader->buildConfiguration(
									$transformationConfigurationXml,
									$transformationConfiguration
								);
							}
						}
						// add the built transformation configuration to the parameters configuration object
						$configuration->addTransformationConfiguration($transformationConfiguration);
					}
				}
			}
		}

		return $configuration;
	}
}

?>