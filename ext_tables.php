<?php

use Nawork\NaworkUri\Command\MigrationCommandController;
use Nawork\NaworkUri\Command\NaworkUriCommandController;
use Nawork\NaworkUri\Hooks\ClearCache;
use Nawork\NaworkUri\Hooks\ClickMenu;
use Nawork\NaworkUri\Hooks\TceFormsMainFields;
use Nawork\NaworkUri\Hooks\TceMainProcessDatamap;

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

if (defined('TYPO3_MODE') && TYPO3_MODE == 'BE') {
    $mainModuleName = 'naworkuri';
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        $mainModuleName,
        '',
        '',
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('nawork_uri') . 'Configuration/Module/',
        [
            'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/module.png',
            'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod_main.xml',
        ]
    );

	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule('Nawork.' . $_EXTKEY, $mainModuleName, 'uri', '', array(
		'Url' => 'indexUrls,loadUrls,updateSettings,lock,unlock,delete,deleteSelected,message'
		), array(
		'access' => 'user,group',
		'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/module.png',
		'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod_url.xml',
		'navigationComponentId' => 'typo3-pagetree'
	));

	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule('Nawork.' . $_EXTKEY, $mainModuleName, 'redirect', '', array(
		'Url' => 'indexRedirects,loadRedirects,updateSettings,delete,deleteSelected,message'
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
	if (TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3\CMS\Core\Utility\VersionNumberUtility::getCurrentTypo3Version()) < 8007000) {
        /* register the ajax ids for the clear cache options (urls and url configuration) */
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
            'tx_naworkuri::clearUrlCache',
            '&' . \Nawork\NaworkUri\Cache\ClearCache::class . '->clearUrlCache',
            false
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
            'tx_naworkuri::clearUrlConfigurationCache',
            '&' . \Nawork\NaworkUri\Cache\ClearCache::class . '->clearConfigurationCache',
            false
        );
    }

	// register command controller for uri testing
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = NaworkUriCommandController::class;
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = MigrationCommandController::class;

    // click menu processing
    if (TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3\CMS\Core\Utility\VersionNumberUtility::getCurrentTypo3Version()) <= 8005000) {
        $GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = [
            'name' => ClickMenu::class
        ];
    } else {
	    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:nawork_uri/Configuration/TSConfig/module.ts">');
        $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1486554866] = Nawork\NaworkUri\ContextMenu\ItemProvider::class;
    }
    // register icons
    /** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    // register record icons
    $iconRegistry->registerIcon(
        'tcarecords-tx_naworkuri_uri-default',
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        ['source' => 'EXT:nawork_uri/Resources/Public/Icons/link.svg']
    );
    $iconRegistry->registerIcon(
        'tcarecords-tx_naworkuri_uri-locked',
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        ['source' => 'EXT:nawork_uri/Resources/Public/Icons/link-locked.svg']
    );
    $iconRegistry->registerIcon(
        'tcarecords-tx_naworkuri_uri-old',
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        ['source' => 'EXT:nawork_uri/Resources/Public/Icons/link-old.svg']
    );
    $iconRegistry->registerIcon(
        'tcarecords-tx_naworkuri_uri-redirect',
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        ['source' => 'EXT:nawork_uri/Resources/Public/Icons/link-redirect.svg']
    );
    // register action icons for click menu
    $iconRegistry->registerIcon(
        'action-url-lock',
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        ['source' => 'EXT:nawork_uri/Resources/Public/Icons/lock.svg']
    );
    $iconRegistry->registerIcon(
        'action-url-unlock',
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        ['source' => 'EXT:nawork_uri/Resources/Public/Icons/unlock.svg']
    );
    $iconRegistry->registerIcon(
        'tx-naworkuri',
        TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
        ['source' => 'EXT:nawork_uri/Resources/Public/Icons/module.png']
    );
}
