<?php

	// hook into typolink to comvert params 2 path
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['linkData-PostProc']['nawork_uri'] = 'EXT:nawork_uri/class.tx_naworkuri.php:&tx_naworkuri->params2uri';
	// hook to extract params from path
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc']['nawork_uri'] = 'EXT:nawork_uri/class.tx_naworkuri.php:&tx_naworkuri->uri2params';
	// redirect old urls to new
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['isOutputting']['nawork_uri'] = 'EXT:nawork_uri/class.tx_naworkuri.php:&tx_naworkuri->redirect2uri';
	// add a tce_main hook to create the path and parameter hashes automatically
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['nawork_uri'] = 'EXT:nawork_uri/class.tx_naworkuri.php:tx_naworkuri';

$TYPO3_CONF_VARS['BE']['AJAX']['tx_naworkuri::getpageinfo'] = 'EXT:nawork_uri/classes/ajax/class.tx_naworkuri_PageInfo.php:tx_naworkuri_PageInfo->getPageInfo';
$TYPO3_CONF_VARS['BE']['AJAX']['tx_naworkuri::getpageuris'] = 'EXT:nawork_uri/classes/ajax/class.tx_naworkuri_PageInfo.php:tx_naworkuri_PageInfo->getPageUris';
$TYPO3_CONF_VARS['BE']['AJAX']['tx_naworkuri::modpageuris'] = 'EXT:nawork_uri/classes/ajax/class.tx_naworkuri_PageInfo.php:tx_naworkuri_PageInfo->modPageUris';
$TYPO3_CONF_VARS['BE']['AJAX']['tx_naworkuri::searchpageuris'] = 'EXT:nawork_uri/classes/ajax/class.tx_naworkuri_PageInfo.php:tx_naworkuri_PageInfo->searchPageUris';

 
?>
