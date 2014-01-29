<?php

// hook into typolink to comvert params 2 path
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['linkData-PostProc']['nawork_uri'] = 'EXT:nawork_uri/Classes/Controller/Frontend/UrlController.php:&Nawork\NaworkUri\Controller\Frontend\UrlController->params2uri';
// hook to extract params from path
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc']['nawork_uri'] = 'EXT:nawork_uri/Classes/Controller/Frontend/UrlController.php:&Nawork\NaworkUri\Controller\Frontend\UrlController->uri2params';
// redirect old urls to new
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['configArrayPostProc']['nawork_uri'] = 'EXT:nawork_uri/Classes/Controller/Frontend/UrlController.php:&Nawork\NaworkUri\Controller\Frontend\UrlController->redirect2uri';
// add a tce_main hook to create the path and parameter hashes automatically
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['nawork_uri'] = 'EXT:nawork_uri/Classes/Controller/Frontend/UrlController.php:UrlController';
//$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['tx_naworkuri_uriSave'] = 'EXT:nawork_uri/Classes/UserFunc/UriSave.php:Tx_NaworkUri_UserFunc_UriSave';
/* add an additional cache clearing function to the menu */
$TYPO3_CONF_VARS['SC_OPTIONS']['additionalBackendItems']['cacheActions']['tx_naworkuri'] = 'EXT:nawork_uri/Classes/Cache/tx_naworkuri_cache_clearcachemenu.php:tx_naworkuri_cache_clearcachemenu';
//
$TYPO3_CONF_VARS['BE']['AJAX']['tx_naworkuri::clearUrlCache'] = 'EXT:nawork_uri/Classes/Cache/ClearCache.php:&Tx_NaworkUri_Cache_ClearCache->clearUrlCache';

$TYPO3_CONF_VARS['SC_OPTIONS']['tce']['formevals']['tx_naworkuri_path'] = 'EXT:nawork_uri/Classes/Validation/class.tx_naworkuri_path.php';

if (TYPO3_MODE === 'BE') {
	/* Register command controller, this works since 4.6 */
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'Tx_Naworkuri_Command_NaworkUriCommandController';
}

// array to store the transformation services in
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['transformationServices'] = array();

\Nawork\NaworkUri\Utility\GeneralUtility::registerTransformationService('Hidden', 'EXT:nawork_uri/Classes/Service/Transformation/HiddenTransformationService.php:Nawork\\NaworkUri\\Service\\Transformation\\HiddenTransformationService');
\Nawork\NaworkUri\Utility\GeneralUtility::registerTransformationService('Plain', 'EXT:nawork_uri/Classes/Service/Transformation/PlainTransformationService.php:Nawork\\NaworkUri\\Service\\Transformation\\PlainTransformationService');
\Nawork\NaworkUri\Utility\GeneralUtility::registerTransformationService('ValueMap', 'EXT:nawork_uri/Classes/Service/Transformation/ValueMapTransformationService.php:Nawork\\NaworkUri\\Service\\Transformation\\ValueMapTransformationService');
\Nawork\NaworkUri\Utility\GeneralUtility::registerTransformationService('PagePath', 'EXT:nawork_uri/Classes/Service/Transformation/PagePathTransformationService.php:Nawork\\NaworkUri\\Service\\Transformation\\PagePathTransformationService');
\Nawork\NaworkUri\Utility\GeneralUtility::registerTransformationService('Database', 'EXT:nawork_uri/Classes/Service/Transformation/DatabaseTransformationService.php:Nawork\\NaworkUri\\Service\\Transformation\\DatabaseTransformationService');
?>
