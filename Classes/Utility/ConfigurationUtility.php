<?php

namespace Nawork\NaworkUri\Utility;

use Nawork\NaworkUri\Configuration\Configuration;
use Nawork\NaworkUri\Configuration\GeneralConfiguration;
use Nawork\NaworkUri\Configuration\PageNotAccessibleConfiguration;
use Nawork\NaworkUri\Configuration\PageNotFoundConfiguration;
use Nawork\NaworkUri\Configuration\PageNotTranslatedConfiguration;
use Nawork\NaworkUri\Configuration\ParametersConfiguration;
use Nawork\NaworkUri\Configuration\TableConfiguration;
use Nawork\NaworkUri\Configuration\TransliterationsConfiguration;
use Nawork\NaworkUri\Exception\InheritanceException;
use Nawork\NaworkUri\Exception\InvalidConfigurationException;
use Nawork\NaworkUri\Transformation\AbstractConfigurationReader;
use Nawork\NaworkUri\Transformation\AbstractTransformationConfiguration;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConfigurationUtility
{
    /**
     * @var \Nawork\NaworkUri\Configuration\Configuration
     */
    protected static $configuration = null;

    /**
     * @var \Nawork\NaworkUri\Configuration\Configuration[]
     */
    protected static $configurations = [];

    /**
     * @var \Nawork\NaworkUri\Configuration\Configuration[]
     */
    protected static $configurationsByIdentifier = [];

    protected static $inheritanceLock = [];

    public static function getConfigurationFileForCurrentDomain()
    {
        /** @var \Nawork\NaworkUri\Configuration\TableConfiguration $tableConfiguration */
        $tableConfiguration = GeneralUtility::makeInstance(TableConfiguration::class);
        $file = null;
        try {
            // try to find the configuration for the current host name, e.g. for
            // local development or testing environment: this ignores master domains
            $file = self::findConfigurationByDomain(GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY'));
        } catch (InvalidConfigurationException $ex) {
            $domain = 'default';
            // look, if there is a domain record matching the current hostname,
            // this includes recursive look up of master domain records
            $domainUid = \Nawork\NaworkUri\Utility\GeneralUtility::getCurrentDomain();
            if ($domainUid > 0) {
                $domainRecord = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableConfiguration->getDomainTable())
                    ->select(['domainName'], $tableConfiguration->getDomainTable(), ['uid' => $domainUid])->fetch();
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

    private static function findConfigurationByDomain($domain)
    {
        if (!array_key_exists($domain, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['configurations'])) {
            throw new InvalidConfigurationException('No configuration for domain \'' . $domain . '\' registered', 1391077835);
        }

        $file = GeneralUtility::getFileAbsFileName($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['configurations'][$domain], true, true);
        if (!file_exists($file) || !is_file($file) && !is_link($file)) {
            throw new InvalidConfigurationException('The configuration file for domain \'' . $domain . '\' does not exist or is not a file/link', 1391077846);
        }

        return $file;
    }

    /**
     * @param string $identifier       The domain name or keyword "default"
     * @param string $path             The path to the configuration file, e.g. EXT:my_ext/Configuration/Url/Default.xml
     * @param bool   $overrideExisting Set to false if you do not want to override an existing configuration for this domain
     */
    public static function registerConfiguration($identifier, $path, $overrideExisting = true)
    {
        if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['Configurations'])) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['Configurations'] = [];
        }
        $absolutePath = GeneralUtility::getFileAbsFileName($path);
        if ($overrideExisting || !array_key_exists($identifier,
                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['Configurations'])
        ) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['Configurations'][$identifier] = $absolutePath;
        }
    }

    public static function getAvailableConfigurations()
    {
        return array_keys($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['Configurations']);
    }

    /**
     * The public method that is to be used to get the configuration object
     *
     * @param string|null $domain
     *
     * @return \Nawork\NaworkUri\Configuration\Configuration
     */
    public static function getConfiguration($domain = null)
    {
        if ($domain !== null || self::$configuration === null) {
            self::$configuration = self::getConfigurationObject($domain);
            if ($domain !== null) {
                self::$configurations[$domain] = self::$configuration;
            }
        }
        // reset inheritance lock after each configuration request
        self::$inheritanceLock = [];

        return self::$configuration;
    }

    /**
     * Try to find a configuration object by trying the following options:
     *
     * 1. current host name
     * 2. determined master domain
     * 3. default configuration
     *
     * @param string|null $domain
     *
     * @return \Nawork\NaworkUri\Configuration\Configuration|false
     *
     * @throws \Nawork\NaworkUri\Exception\InheritanceException
     */
    private static function getConfigurationObject($domain = null)
    {
        try {
            return self::getConfigurationObjectForDomain($domain);
        } catch (\Exception $ex) {
            // inheritance exceptions must bubble up
            if ($ex instanceof InheritanceException) {
                throw $ex;
            }
            try {
                return self::getConfigurationObjectForDomain(GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY'));
            } catch (\RuntimeException $e) {
                if ($e->getCode() === 1475831209) {
                    // no configuration for this domain, meaning nawork_uri is disabled
                    return false;
                }
                // otherwise throw the exception again
                throw $e;
            } catch (\Exception $ex) {
                // inheritance exceptions must bubble up
                if ($ex instanceof InheritanceException) {
                    throw $ex;
                }
                try {
                    return self::getConfigurationObjectForDomain(
                        \Nawork\NaworkUri\Utility\GeneralUtility::getCurrentDomainName()
                    );
                } catch (\RuntimeException $e) {
                    if ($e->getCode() === 1475831209) {
                        // no configuration for this domain, meaning nawork_uri is disabled
                        return false;
                    }
                    // otherwise throw the exception again
                    throw $e;
                } catch (\Exception $ex) {
                    // inheritance exceptions must bubble up
                    if ($ex instanceof InheritanceException) {
                        throw $ex;
                    }

                    return false;
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
    private static function getConfigurationObjectForDomain($domain)
    {
        if (array_key_exists($domain, self::$configurations)) {
            return self::$configurations[$domain];
        }

        $domainUid = \Nawork\NaworkUri\Utility\GeneralUtility::getCurrentDomain($domain);
        $identifier = \Nawork\NaworkUri\Utility\GeneralUtility::getDomainConfigurationIdentifier($domainUid);

        return self::getConfigurationByIdentifier($identifier);
    }

    private static function getConfigurationByIdentifier($identifier)
    {
        if (array_key_exists($identifier, self::$configurationsByIdentifier)) {
            return self::$configurationsByIdentifier[$identifier];
        }
        try {
            self::$configurationsByIdentifier[$identifier] = self::readCompiledConfigurationFile($identifier);

            return self::$configurationsByIdentifier[$identifier];
        } catch (\Exception $e) {
            if (array_key_exists($identifier, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['Configurations'])) {
                self::$configurationsByIdentifier[$identifier] = self::buildConfiguration($identifier);
                self::storeCompiledConfigurationToFile(
                    self::$configurationsByIdentifier[$identifier],
                    $identifier
                );

                return self::$configurationsByIdentifier[$identifier];
            }
            throw new \Exception('No configuration is registered with identifer "' . $identifier . '"', 1394135040);
        }
    }

    private static function storeCompiledConfigurationToFile($configuration, $identifier)
    {
        $cacheIdentifier = md5($identifier);
        /** @var FrontendInterface $cache */
        $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('naworkuri_configuration');
        $cache->set($cacheIdentifier, $configuration);
    }

    /**
     * Read and unserialize the configuration file, if it exists. Throw an exception if not.
     *
     * @param string $identifier
     *
     * @return \Nawork\NaworkUri\Configuration\Configuration
     *
     * @throws \Exception
     */
    private static function readCompiledConfigurationFile($identifier)
    {
        $cacheIdentifier = md5($identifier);
        $object = GeneralUtility::makeInstance(CacheManager::class)->getCache('naworkuri_configuration')->get($cacheIdentifier);
        if (!$object instanceof Configuration) {
            throw new \Exception('No configuration with identifier "' . $identifier . '" could be retrieved from cache');
        }

        return $object;
    }

    /**
     * Determine the xml configuration file from registered configurations and put it
     * into a SimpleXMLElement
     *
     * @param string $identifier
     *
     * @return \SimpleXMLElement
     *
     * @throws \Exception
     */
    private static function readXmlConfigurationFile($identifier)
    {
        if (!array_key_exists($identifier, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['Configurations'])) {
            throw new \Exception('No configuration registered with identifier "' . $identifier . '"', 1394137785);
        }
        $file = GeneralUtility::getFileAbsFileName($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['Configurations'][$identifier]);
        if (file_exists($file) && is_readable($file)) {
            return new \SimpleXMLElement($file, 0, true);
        }
        throw new \Exception('The xml configuration file "' . $file . '" does not exist, or is not readable', 1394137986);
    }

    /**
     * Read xml configuration file and build a configuration object out of it
     *
     * @param string $identifier
     *
     * @return \Nawork\NaworkUri\Configuration\Configuration
     *
     * @throws \Nawork\NaworkUri\Exception\InheritanceException
     * @internal
     */
    public static function buildConfiguration($identifier)
    {
        $xml = self::readXmlConfigurationFile($identifier);
        $configuration = null;
        if ($xml->attributes()->extends && strcmp('', (string)$xml->attributes()->extends)) {
            $extendedDomainName = (string)$xml->attributes()->extends;
            // the domain is already used for extending this type of configuration, throw an exception
            if (self::$inheritanceLock[$extendedDomainName]) {
                throw new InheritanceException('UrlConfiguration', $extendedDomainName);
            }
            try {
                self::$inheritanceLock[$extendedDomainName] = true;
                $configuration = self::getConfigurationByIdentifier((string)$xml->attributes()->extends);
            } catch (\Exception $e) {
                // inheritance exceptions must bubble up
                if ($e instanceof InheritanceException) {
                    throw $e;
                }
                self::$inheritanceLock[$extendedDomainName] = false;
            }
        }
        if ($configuration === null) {
            $configuration = new Configuration();
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
     * @param \SimpleXMLElement                                    $xml
     * @param \Nawork\NaworkUri\Configuration\GeneralConfiguration $configuration |NULL
     *
     * @return \Nawork\NaworkUri\Configuration\GeneralConfiguration
     *
     * @throws \Nawork\NaworkUri\Exception\InheritanceException
     */
    private static function buildGeneralConfiguration($xml, $configuration)
    {
        // if there is an extends set it overrides already given $configuration
        if ($xml && $xml->attributes()->extends && strcmp('', (string)$xml->attributes()->extends)) {
            $extendedDomainName = (string)$xml->attributes()->extends;
            // the domain is already used for extending this type of configuration, throw an exception
            if (self::$inheritanceLock[$extendedDomainName . '.General']) {
                throw new InheritanceException('GeneralConfiguration', $extendedDomainName);
            }
            try {
                self::$inheritanceLock[$extendedDomainName . '.General'] = true;
                $configuration = self::getConfigurationByIdentifier((string)$xml->attributes()->extends)
                    ->getGeneralConfiguration();
            } catch (\Exception $e) {
                if ($e instanceof InheritanceException) {
                    throw $e;
                }
                $configuration = null;
                self::$inheritanceLock[$extendedDomainName . '.General'] = false;
            }
        }
        if ($configuration === null) {
            $configuration = new GeneralConfiguration();
        }
        if ($xml->Append) {
            $configuration->setAppend((string)$xml->Append);
        }
        if ($xml->AppendIfNotPattern) {
            $configuration->setAppendIfNotPattern((string)$xml->AppendIfNotPattern);
        }
        $configuration->setDisabled((bool)(int)$xml->Disabled);
        if ($xml->PathSeparator) {
            $configuration->setPathSeparator((string)$xml->PathSeparator);
        }
        if ($xml->RedirectStatus) {
            $configuration->setRedirectStatus((string)$xml->RedirectStatus);
        }

        return $configuration;
    }

    /**
     * @param $xml           \SimpleXMLElement
     * @param $configuration \Nawork\NaworkUri\Configuration\TransliterationsConfiguration
     *
     * @throws \Nawork\NaworkUri\Exception\InheritanceException
     *
     * @return TransliterationsConfiguration
     */
    private static function buildTransliterationsConfiguration($xml, $configuration)
    {
        // if there is an extends set it overrides already given $configuration
        if ($xml && $xml->attributes()->extends && strcmp('', (string)$xml->attributes()->extends)) {
            $extendedDomainName = (string)$xml->attributes()->extends;
            // the domain is already used for extending this type of configuration, throw an exception
            if (self::$inheritanceLock[$extendedDomainName . '.Transliterations']) {
                throw new InheritanceException('TransliterationsConfiguration', $extendedDomainName);
            }
            try {
                self::$inheritanceLock[$extendedDomainName . '.Transliterations'] = true;
                $configuration = self::getConfigurationByIdentifier((string)$xml->attributes()->extends)
                    ->getTransliterationsConfiguration();
            } catch (\Exception $e) {
                if ($e instanceof InheritanceException) {
                    throw $e;
                }
                $configuration = null;
                self::$inheritanceLock[$extendedDomainName . '.Transliterations'] = false;
            }
        }
        if ($configuration === null) {
            $configuration = new TransliterationsConfiguration();
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

    /**
     * @param \SimpleXMLElement         $xml
     * @param PageNotFoundConfiguration $configuration
     *
     * @return \Nawork\NaworkUri\Configuration\PageNotFoundConfiguration|null
     * @throws InheritanceException
     */
    private static function buildPageNotFoundConfiguration($xml, $configuration)
    {
        // if there is an extends set it overrides already given $configuration
        if ($xml && $xml->attributes()->extends && strcmp('', (string)$xml->attributes()->extends)) {
            $extendedDomainName = (string)$xml->attributes()->extends;
            // the domain is already used for extending this type of configuration, throw an exception
            if (self::$inheritanceLock[$extendedDomainName . '.PageNotFound']) {
                throw new InheritanceException('PageNotFoundConfiguration', $extendedDomainName);
            }
            try {
                self::$inheritanceLock[$extendedDomainName . '.PageNotFound'] = true;
                $configuration = self::getConfigurationByIdentifier((string)$xml->attributes()->extends)
                    ->getPageNotFoundConfiguration();
            } catch (\Exception $e) {
                if ($e instanceof InheritanceException) {
                    throw $e;
                }
                $configuration = null;
                self::$inheritanceLock[$extendedDomainName . '.PageNotFound'] = false;
            }
        }
        if ($configuration === null) {
            $configuration = new PageNotFoundConfiguration();
        }
        if ($xml->Behavior && strcmp('', (string)$xml->Behavior)) {
            switch ((string)$xml->Behavior) {
                case 'Page':
                    $configuration->setBehavior(PageNotFoundConfiguration::BEHAVIOR_PAGE);
                    break;
                case 'Redirect':
                    $configuration->setBehavior(PageNotFoundConfiguration::BEHAVIOR_REDIRECT);
                    break;
                case 'Message':
                default:
                    $configuration->setBehavior(PageNotFoundConfiguration::BEHAVIOR_MESSAGE);
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
     * @param \SimpleXMLElement              $xml
     * @param PageNotAccessibleConfiguration $configuration
     *
     * @return \Nawork\NaworkUri\Configuration\PageNotAccessibleConfiguration|null
     * @throws InheritanceException
     */
    private static function buildPageNotAccessibleConfiguration($xml, $configuration)
    {
        // if there is an extends set it overrides already given $configuration
        if ($xml && $xml->attributes()->extends && strcmp('', (string)$xml->attributes()->extends)) {
            $extendedDomainName = (string)$xml->attributes()->extends;
            // the domain is already used for extending this type of configuration, throw an exception
            if (self::$inheritanceLock[$extendedDomainName . '.PageNotAccessible']) {
                throw new InheritanceException('PageNotAccessibleConfiguration', $extendedDomainName);
            }
            try {
                self::$inheritanceLock[$extendedDomainName . '.PageNotAccessible'] = true;
                $configuration = self::getConfigurationByIdentifier((string)$xml->attributes()->extends)
                    ->getPageNotAccessibleConfiguration();
            } catch (\Exception $e) {
                if ($e instanceof InheritanceException) {
                    throw $e;
                }
                $configuration = null;
                self::$inheritanceLock[$extendedDomainName . '.PageNotAccessible'] = false;
            }
        }
        if ($configuration === null) {
            $configuration = new PageNotAccessibleConfiguration();
        }
        if ($xml->Behavior && strcmp('', (string)$xml->Behavior)) {
            switch ((string)$xml->Behavior) {
                case 'Page':
                    $configuration->setBehavior(PageNotAccessibleConfiguration::BEHAVIOR_PAGE);
                    break;
                case 'Redirect':
                    $configuration->setBehavior(PageNotAccessibleConfiguration::BEHAVIOR_REDIRECT);
                    break;
                case 'Message':
                default:
                    $configuration->setBehavior(PageNotAccessibleConfiguration::BEHAVIOR_MESSAGE);
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
     * @param \SimpleXMLElement              $xml
     * @param PageNotTranslatedConfiguration $configuration
     *
     * @return \Nawork\NaworkUri\Configuration\PageNotTranslatedConfiguration|null
     * @throws InheritanceException
     */
    private static function buildPageNotTranslatedConfiguration($xml, $configuration)
    {
        // if there is an extends set it overrides already given $configuration
        if ($xml && $xml->attributes()->extends && strcmp('', (string)$xml->attributes()->extends)) {
            $extendedDomainName = (string)$xml->attributes()->extends;
            // the domain is already used for extending this type of configuration, throw an exception
            if (self::$inheritanceLock[$extendedDomainName . '.PageNotTranslated']) {
                throw new InheritanceException('PageNotTranslatedConfiguration', $extendedDomainName);
            }
            try {
                self::$inheritanceLock[$extendedDomainName . '.PageNotTranslated'] = true;
                $configuration = self::getConfigurationByIdentifier((string)$xml->attributes()->extends)
                    ->getPageNotTranslatedConfiguration();
            } catch (\Exception $e) {
                if ($e instanceof InheritanceException) {
                    throw $e;
                }
                $configuration = null;
                self::$inheritanceLock[$extendedDomainName . '.PageNotTranslated'] = false;
            }
        }
        if ($configuration === null) {
            $configuration = new PageNotTranslatedConfiguration();
        }
        if ($xml->Behavior && strcmp('', (string)$xml->Behavior)) {
            switch ((string)$xml->Behavior) {
                case 'Page':
                    $configuration->setBehavior(PageNotTranslatedConfiguration::BEHAVIOR_PAGE);
                    break;
                case 'Redirect':
                    $configuration->setBehavior(PageNotTranslatedConfiguration::BEHAVIOR_REDIRECT);
                    break;
                case 'Message':
                default:
                    $configuration->setBehavior(PageNotTranslatedConfiguration::BEHAVIOR_MESSAGE);
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
     * @param \SimpleXMLElement                                       $xml
     * @param \Nawork\NaworkUri\Configuration\ParametersConfiguration $configuration
     *
     * @return \Nawork\NaworkUri\Configuration\ParametersConfiguration
     *
     * @throws \Nawork\NaworkUri\Exception\InheritanceException
     */
    private static function buildParametersConfiguration($xml, $configuration)
    {
        // if there is an extends set it overrides already given $configuration
        if ($xml && $xml->attributes()->extends && strcmp('', (string)$xml->attributes()->extends)) {
            $extendedDomainName = (string)$xml->attributes()->extends;
            // the domain is already used for extending this type of configuration, throw an exception
            if (self::$inheritanceLock[$extendedDomainName . '.Parameters']) {
                throw new InheritanceException('ParametersConfiguration', $extendedDomainName);
            }
            try {
                self::$inheritanceLock[$extendedDomainName . '.Parameters'] = true;
                $configuration = self::getConfigurationByIdentifier((string)$xml->attributes()->extends)
                    ->getParametersConfiguration();
            } catch (\Exception $e) {
                if ($e instanceof InheritanceException) {
                    throw $e;
                }
                self::$inheritanceLock[$extendedDomainName . '.Parameters'] = false;
            }
        }
        if ($configuration === null) {
            $configuration = new ParametersConfiguration();
        }
        if ($xml) {
            /* @var $transformationConfigurationXml \SimpleXMLElement */
            foreach ($xml->children() as $transformationConfigurationXml) {
                $transformationConfiguration = null;
                if ($transformationConfigurationXml->getName() == 'TransformationConfiguration') {
                    $parameterName = (string)$transformationConfigurationXml->Name;
                    if ($transformationConfigurationXml->attributes()->extends && strcmp('',
                            (string)$transformationConfigurationXml->attributes()->extends)
                    ) {
                        $extendedDomainName = (string)$transformationConfigurationXml->attributes()->extends;
                        // the domain is already used for extending this type of configuration, throw an exception
                        if (self::$inheritanceLock[$extendedDomainName . '.Parameters.' . $parameterName]) {
                            throw new InheritanceException('TransformationConfiguration:' . $parameterName, $extendedDomainName);
                        }
                        try {
                            self::$inheritanceLock[$extendedDomainName . '.Parameters.' . $parameterName] = true;
                            $transformationConfiguration = self::getConfigurationByIdentifier((string)$transformationConfigurationXml->attributes()->extends)
                                ->getParametersConfiguration()
                                ->getParameterTransformationConfigurationByName($parameterName);
                        } catch (\Exception $e) {
                            if ($e instanceof InheritanceException) {
                                throw $e;
                            }
                            self::$inheritanceLock[$extendedDomainName . '.Parameters.' . $parameterName] = false;
                        }
                    }
                    if ($transformationConfiguration === null || (strcmp('', (string)$transformationConfigurationXml->Type) !== 0 && $transformationConfiguration->getType() !== (string)$transformationConfigurationXml->Type)) {
                        $type = (string)$transformationConfigurationXml->Type;
                    } else {
                        $type = $transformationConfiguration->getType();
                    }
                    if (TransformationUtility::isTransformationServiceRegistered($type)) {
                        $transformationServiceClassName = TransformationUtility::getTransformationServiceClassName($type);
                        $classNameParts = GeneralUtility::trimExplode('\\', $transformationServiceClassName);
                        if ($transformationConfiguration === null || $transformationConfiguration->getType() !== $type) {
                            // create a new transformation configuration object, if there is non or the type has changed
                            // build configuration class name
                            array_pop($classNameParts);
                            array_push($classNameParts, 'TransformationConfiguration');
                            $transformationConfigurationClassName = implode('\\', $classNameParts);
                            // if the configuration class does not exist - and can not be loaded - log this and continue
                            // this leaves this parameter out of the configuration
                            if (!class_exists($transformationConfigurationClassName)) {
                                \Nawork\NaworkUri\Utility\GeneralUtility::log(
                                    'The transformation configuration class "%s" does not exist. Transformation service: %s. Referrer: %s',
                                    LogLevel::ERROR,
                                    [
                                        $transformationConfigurationClassName,
                                        $transformationServiceClassName,
                                        GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL')
                                    ]
                                );
                            }
                            /* @var $transformationConfiguration AbstractTransformationConfiguration */
                            $transformationConfiguration = new $transformationConfigurationClassName();
                            $transformationConfiguration->setName((string)$transformationConfigurationXml->Name);
                        }
                        // build the configuration based in the given properties
                        foreach ($transformationConfiguration->getAdditionalProperties() as $name => $type) {
                            // exclude name and type values as there are set already
                            if (!in_array($name, ['Name', 'Type'])) {
                                $setFunctionName = 'set' . ucfirst($name);
                                if ($transformationConfigurationXml->$name && $transformationConfigurationXml->$name instanceof \SimpleXMLElement && method_exists($transformationConfiguration, $setFunctionName)) {
                                    $value = null;
                                    switch ($type) {
                                        case 'bool':
                                        case 'boolean':
                                            $value = (bool)(int)$transformationConfigurationXml->$name;
                                            break;
                                        case 'int':
                                        case 'integer':
                                            $value = (int)$transformationConfigurationXml->$name;
                                            break;
                                        case 'string':
                                            $value = (string)$transformationConfigurationXml->$name;
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
                            $configurationReader = GeneralUtility::makeInstance($configurationReaderClassName);
                            if ($configurationReader instanceof AbstractConfigurationReader) {
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
