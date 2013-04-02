<?php

	// hook into typolink to comvert params 2 path
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['linkData-PostProc']['nawork_uri'] = 'EXT:nawork_uri/class.tx_naworkuri.php:&tx_naworkuri->params2uri';
	// hook to extract params from path
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc']['nawork_uri'] = 'EXT:nawork_uri/class.tx_naworkuri.php:&tx_naworkuri->uri2params';
	// redirect old urls to new
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['configArrayPostProc']['nawork_uri'] = 'EXT:nawork_uri/class.tx_naworkuri.php:&tx_naworkuri->redirect2uri';
	// add a tce_main hook to create the path and parameter hashes automatically
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['nawork_uri'] = 'EXT:nawork_uri/class.tx_naworkuri.php:tx_naworkuri';
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['tx_naworkuri_uriSave'] = 'EXT:nawork_uri/Classes/UserFunc/UriSave.php:Tx_NaworkUri_UserFunc_UriSave';
/* add an additional cache clearing function to the menu */
$TYPO3_CONF_VARS['SC_OPTIONS']['additionalBackendItems']['cacheActions']['tx_naworkuri'] = 'EXT:nawork_uri/Classes/Cache/tx_naworkuri_cache_clearcachemenu.php:tx_naworkuri_cache_clearcachemenu';
//
//$TYPO3_CONF_VARS['BE']['AJAX']['tx_naworkuri::getpageinfo'] = 'EXT:nawork_uri/classes/ajax/class.tx_naworkuri_PageInfo.php:tx_naworkuri_PageInfo->getPageInfo';
//$TYPO3_CONF_VARS['BE']['AJAX']['tx_naworkuri::getpageuris'] = 'EXT:nawork_uri/classes/ajax/class.tx_naworkuri_PageInfo.php:tx_naworkuri_PageInfo->getPageUris';
//$TYPO3_CONF_VARS['BE']['AJAX']['tx_naworkuri::modpageuris'] = 'EXT:nawork_uri/classes/ajax/class.tx_naworkuri_PageInfo.php:tx_naworkuri_PageInfo->modPageUris';
//$TYPO3_CONF_VARS['BE']['AJAX']['tx_naworkuri::searchpageuris'] = 'EXT:nawork_uri/classes/ajax/class.tx_naworkuri_PageInfo.php:tx_naworkuri_PageInfo->searchPageUris';
//$TYPO3_CONF_VARS['BE']['AJAX']['tx_naworkuri::getlanguages'] = 'EXT:nawork_uri/classes/ajax/class.tx_naworkuri_PageInfo.php:tx_naworkuri_PageInfo->getLanguages';
//$TYPO3_CONF_VARS['BE']['AJAX']['tx_naworkuri::checkpathunique'] = 'EXT:nawork_uri/Classes/Validation/class.tx_naworkuri_path.php:tx_naworkuri_path->checkPathUnique';
$TYPO3_CONF_VARS['BE']['AJAX']['tx_naworkuri::clearUrlCache'] = 'EXT:nawork_uri/Classes/Cache/ClearCache.php:&Tx_NaworkUri_Cache_ClearCache->clearUrlCache';

$TYPO3_CONF_VARS['SC_OPTIONS']['tce']['formevals']['tx_naworkuri_path'] = 'EXT:nawork_uri/Classes/Validation/class.tx_naworkuri_path.php';

if (TYPO3_MODE === 'BE') {
	// Register commands
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'Tx_Naworkuri_Command_NaworkUriCommandController';
}

?>
