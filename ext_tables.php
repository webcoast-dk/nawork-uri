<?php

use Nawork\NaworkUri\Command\MigrationCommandController;
use Nawork\NaworkUri\Command\NaworkUriCommandController;
use Nawork\NaworkUri\Hooks\ClearCache;
use Nawork\NaworkUri\Hooks\TceFormsMainFields;
use Nawork\NaworkUri\Hooks\TceMainProcessDatamap;

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

if (defined('TYPO3_MODE') && TYPO3_MODE == 'BE') {
    $mainModuleName = 'naworkuri';
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule($mainModuleName, '', '', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('nawork_uri').'Configuration/Module/');

	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule('Nawork.' . $_EXTKEY, $mainModuleName, 'uri', '', array(
		'Url' => 'indexUrls,ajaxLoadUrls,updateSettings,contextMenu,lockToggle,delete'
		), array(
		'access' => 'user,group',
		'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/module.png',
		'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod_url.xml',
		'navigationComponentId' => 'typo3-pagetree'
	));

	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule('Nawork.' . $_EXTKEY, $mainModuleName, 'redirect', '', array(
		'Url' => 'indexRedirects,ajaxLoadRedirects,updateSettings,contextMenu,lockToggle,delete'
		), array(
		'access' => 'user,group',
		'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/module.png',
		'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod_redirect.xml'
	));

	$extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nawork_uri']);
	if ($extensionConfiguration['configurationModuleEnable']) {
		\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule('Nawork.' . $_EXTKEY, $mainModuleName, 'configuration', '', array(
				'Configuration' => 'index,show'
		), array(
				'access' => 'user,group',
				'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/module.png',
				'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod_configuration.xml'
		));
	}

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:nawork_uri/Configuration/TypoScript/module.ts">');

	// register hook for manipulating default type for new records
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getMainFieldsClass'][] = TceFormsMainFields::class;
	// add a hook to create the path and parameter hashes automatically when creating or altering urls manually
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = TceMainProcessDatamap::class;
	// add an additional cache clearing function to the menu
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions'][] = ClearCache::class;
    /* register the ajax ids for the clear cache options (urls and url configuration) */
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
        'tx_naworkuri::clearUrlCache',
        '&' . \Nawork\NaworkUri\Cache\ClearCache::class . '->clearUrlCache',
        FALSE
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
        'tx_naworkuri::clearUrlConfigurationCache',
        '&' . \Nawork\NaworkUri\Cache\ClearCache::class . '->clearConfigurationCache',
        FALSE
    );

	// register command controller for uri testing
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = NaworkUriCommandController::class;
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = MigrationCommandController::class;
}
