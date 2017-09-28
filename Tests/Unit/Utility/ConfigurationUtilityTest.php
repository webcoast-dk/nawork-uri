<?php

namespace Nawork\NaworkUri\Tests\Unit\Utility;


use Nawork\NaworkUri\Configuration\PageNotFoundConfiguration;
use Nawork\NaworkUri\Exception\InheritanceException;
use Nawork\NaworkUri\Utility\ConfigurationUtility;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConfigurationUtilityTest extends TestCase
{
    protected $backupGlobals = true;
    protected $backupStaticAttributes = true;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        require_once __DIR__ . '/../../../ext_localconf.php';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['naworkuri_configuration']['backend'] = NullBackend::class;
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        $cacheManager->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
    }

    /**
     * @test
     */
    public function registerConfigurationTest()
    {
        ConfigurationUtility::registerConfiguration(
            'default',
            'EXT:nawork_uri/Configuration/Url/DefaultConfiguration.xml'
        );
        ConfigurationUtility::registerConfiguration(
            'test',
            'EXT:nawork_uri/Tests/Configuration/Url/TestConfiguration.xml'
        );
        $this->assertArrayHasKey('default', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['Configurations']);
        $this->assertArrayHasKey('test', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['Configurations']);
        $this->assertEquals(
            GeneralUtility::getFileAbsFileName('EXT:nawork_uri/Configuration/Url/DefaultConfiguration.xml'),
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['Configurations']['default']
        );
        $this->assertEquals(
            GeneralUtility::getFileAbsFileName('EXT:nawork_uri/Tests/Configuration/Url/TestConfiguration.xml'),
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['Configurations']['test']
        );
    }

    /**
     * @test
     */
    public function getAvailableConfigurationsTest()
    {
        ConfigurationUtility::registerConfiguration(
            'default',
            'EXT:nawork_uri/Configuration/Url/DefaultConfiguration.xml'
        );
        ConfigurationUtility::registerConfiguration(
            'test',
            'EXT:nawork_uri/Configuration/Url/TestConfiguration.xml'
        );
        $this->assertEquals(['default', 'test'], ConfigurationUtility::getAvailableConfigurations());
    }

    /**
     * @test
     */
    public function buildConfigurationTest()
    {
        ConfigurationUtility::registerConfiguration(
            'default',
            'EXT:nawork_uri/Configuration/Url/DefaultConfiguration.xml'
        );
        $configuration = ConfigurationUtility::buildConfiguration('default');
        $this->assertEquals('/', $configuration->getGeneralConfiguration()->getAppend());
        $this->assertEquals(
            PageNotFoundConfiguration::BEHAVIOR_MESSAGE,
            $configuration->getPageNotFoundConfiguration()->getBehavior()
        );
    }

    /**
     * @test
     */
    public function buildConfigurationInheritanceTest()
    {
        ConfigurationUtility::registerConfiguration(
            'default',
            'EXT:nawork_uri/Configuration/Url/DefaultConfiguration.xml'
        );
        ConfigurationUtility::registerConfiguration(
            'test',
            'EXT:nawork_uri/Tests/Configuration/Url/TestConfiguration.xml'
        );
        $configuration = ConfigurationUtility::buildConfiguration('test');
        $this->assertEquals('.htm', $configuration->getGeneralConfiguration()->getAppend());
        $this->assertEquals('HTTP/1.0 404 Not Found!', $configuration->getPageNotFoundConfiguration()->getStatus());
        $this->assertEquals(
            PageNotFoundConfiguration::BEHAVIOR_PAGE,
            $configuration->getPageNotFoundConfiguration()->getBehavior()
        );
        $this->assertEquals('page-not-found', $configuration->getPageNotFoundConfiguration()->getValue());
        $this->assertCount(5, $configuration->getParametersConfiguration()->getParameterTransformationConfigurations());
        $this->assertEquals(
            'alias//nav_title//title',
            $configuration->getParametersConfiguration()->getParameterTransformationConfigurationByName(
                'id'
            )->getFields()
        );
        $this->assertEquals(
            'en',
            $configuration->getParametersConfiguration()->getParameterTransformationConfigurationByName(
                'L'
            )->getMapping(0, 1)
        );
        $this->assertEquals(
            'fr',
            $configuration->getParametersConfiguration()->getParameterTransformationConfigurationByName(
                'L'
            )->getMapping(0, 2)
        );
    }

    /**
     * @test
     */
    public function buildConfigurationInheritanceChangeTransformationTypeTest()
    {
        ConfigurationUtility::registerConfiguration(
            'default',
            'EXT:nawork_uri/Configuration/Url/DefaultConfiguration.xml'
        );
        ConfigurationUtility::registerConfiguration(
            'test',
            'EXT:nawork_uri/Tests/Configuration/Url/TestChangeTransformationType.xml'
        );
        $configuration = ConfigurationUtility::buildConfiguration('test');
        $this->assertCount(4, $configuration->getParametersConfiguration()->getParameterTransformationConfigurations());
        $this->assertEquals(
            'Plain',
            $configuration->getParametersConfiguration()->getParameterTransformationConfigurationByName(
                'cHash'
            )->getType()
        );
    }

    /**
     * @test
     */
    public function buildConfigurationInheritanceLoopTest()
    {
        ConfigurationUtility::registerConfiguration(
            'default',
            'EXT:nawork_uri/Configuration/Url/DefaultConfiguration.xml'
        );
        ConfigurationUtility::registerConfiguration(
            'test',
            'EXT:nawork_uri/Tests/Configuration/Url/TestInheritanceLoop.xml'
        );
        $this->expectException(InheritanceException::class);
        $configuration = ConfigurationUtility::buildConfiguration('test');
    }

    /**
     * @test
     */
    public function buildConfigurationInheritanceTwiceTest()
    {
        ConfigurationUtility::registerConfiguration(
            'default',
            'EXT:nawork_uri/Configuration/Url/DefaultConfiguration.xml'
        );
        ConfigurationUtility::registerConfiguration(
            'test',
            'EXT:nawork_uri/Tests/Configuration/Url/TestConfiguration.xml'
        );
        ConfigurationUtility::registerConfiguration(
            'test2',
            'EXT:nawork_uri/Tests/Configuration/Url/TestInheritanceTwice.xml'
        );
        $configuration = ConfigurationUtility::buildConfiguration('test2');
        $this->assertCount(5, $configuration->getParametersConfiguration()->getParameterTransformationConfigurations());
        $this->assertEquals(
            'Database',
            $configuration->getParametersConfiguration()->getParameterTransformationConfigurationByName(
                'tx_ttnews[tt_news]'
            )->getType()
        );
        $this->assertEquals(
            '{title}',
            $configuration->getParametersConfiguration()->getParameterTransformationConfigurationByName(
                'tx_ttnews[tt_news]'
            )->getPattern(0)
        );
    }
}