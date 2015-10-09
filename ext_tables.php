<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

if (!defined('TYPO3_MODE'))
	die('Access denied.');


// add new fields to page and pages_language_overlay records
$tempColumns = Array(
	'tx_naworkuri_pathsegment' => array(
		'label' => 'LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:pages.tx_naworkuri_pathsegment',
		'config' => Array(
			'type' => 'input',
			'size' => '60',
			'max' => '60',
		),
	),
	'tx_naworkuri_exclude' => array(
		'label' => 'LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:pages.tx_naworkuri_exclude',
		'config' => Array(
			'type' => 'check',
			'default' => '0'
		)
	)
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $tempColumns, 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', 'tx_naworkuri_pathsegment,tx_naworkuri_exclude', '', 'after:title');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages_language_overlay', $tempColumns, 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages_language_overlay', 'tx_naworkuri_pathsegment,tx_naworkuri_exclude', '', 'after:title');

$tempColumns = array(
	'tx_naworkuri_masterDomain' => array(
		'label' => 'LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:sys_domain.tx_naworkuri_masterDomain',
		'config' => array(
			'type' => 'group',
			'internal_type' => 'db',
			'allowed' => 'sys_domain',
			'size' => '1',
			'minitems' => '0',
			'maxitems' => '1',
			'wizards' => array(
				'suggest' => array(
					'type' => 'suggest'
				)
			)
		),
	),
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('sys_domain', $tempColumns, 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('sys_domain', 'tx_naworkuri_masterDomain');

if (TYPO3_MODE == 'BE') {
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

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:nawork_uri/Configuration/TypoScript/module.ts">');
}

$GLOBALS['TYPO3_CONF_VARS']['FE']['pageOverlayFields'] .= ',tx_naworkuri_pathsegment,tx_naworkuri_exclude';

if (defined('TYPO3_MODE') && TYPO3_MODE == 'BE') {
	// register hook for manipulating default type for new records
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getMainFieldsClass'][] = 'Nawork\\NaworkUri\\Hooks\\TceFormsMainFields';
	// add a hook to create the path and parameter hashes automatically when creating or altering urls manually
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'Nawork\\NaworkUri\\Hooks\\TceMainProcessDatamap';
	// add an additional cache clearing function to the menu
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions'][] = 'Nawork\\NaworkUri\\Hooks\\ClearCache';
    /* register the ajax ids for the clear cache options (urls and url configuration) */
    if (class_exists(
            'TYPO3\\CMS\\Core\\Utility\\VersionNumberUtility'
        ) && TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(
            TYPO3\CMS\Core\Utility\VersionNumberUtility::getCurrentTypo3Version()
        ) > 6002000
    ) {
        ExtensionManagementUtility::registerAjaxHandler(
            'tx_naworkuri::clearUrlCache',
            '&Nawork\\NaworkUri\\Cache\\ClearCache->clearUrlCache'
        );
        ExtensionManagementUtility::registerAjaxHandler(
            'tx_naworkuri::clearUrlConfigurationCache',
            '&Nawork\\NaworkUri\\Cache\\ClearCache->clearConfigurationCache'
        );
    }
    else {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['tx_naworkuri::clearUrlCache'] = '&Nawork\\NaworkUri\\Cache\\ClearCache->clearUrlCache';
        $GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['tx_naworkuri::clearUrlConfigurationCache'] = '&Nawork\\NaworkUri\\Cache\ClearCache->clearConfigurationCache';
    }

	// register command controller for uri testing
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'Nawork\\NaworkUri\\Command\\NaworkUriCommandController';
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'Nawork\\NaworkUri\\Command\\MigrationCommandController';
}
?>
