<?php

// hook into typolink to comvert params 2 path
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['linkData-PostProc']['nawork_uri'] = 'EXT:nawork_uri/Classes/Controller/Frontend/UrlController.php:&Nawork\NaworkUri\Controller\Frontend\UrlController->params2uri';
// hook to extract params from path
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc']['nawork_uri'] = 'EXT:nawork_uri/Classes/Controller/Frontend/UrlController.php:&Nawork\NaworkUri\Controller\Frontend\UrlController->uri2params';
// redirect old urls to new
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['configArrayPostProc']['nawork_uri'] = 'EXT:nawork_uri/Classes/Controller/Frontend/UrlController.php:&Nawork\NaworkUri\Controller\Frontend\UrlController->redirect2uri';
//
$TYPO3_CONF_VARS['BE']['AJAX']['tx_naworkuri::clearUrlCache'] = '&Nawork\\NaworkUri\\Cache\\ClearCache->clearUrlCache';

$TYPO3_CONF_VARS['SC_OPTIONS']['tce']['formevals']['tx_naworkuri_path'] = 'EXT:nawork_uri/Classes/Validation/class.tx_naworkuri_path.php';

// register transformation services
\Nawork\NaworkUri\Utility\GeneralUtility::registerTransformationService('Hidden', 'EXT:nawork_uri/Classes/Service/Transformation/HiddenTransformationService.php:Nawork\\NaworkUri\\Service\\Transformation\\HiddenTransformationService');
\Nawork\NaworkUri\Utility\GeneralUtility::registerTransformationService('Plain', 'EXT:nawork_uri/Classes/Service/Transformation/PlainTransformationService.php:Nawork\\NaworkUri\\Service\\Transformation\\PlainTransformationService');
\Nawork\NaworkUri\Utility\GeneralUtility::registerTransformationService('ValueMap', 'EXT:nawork_uri/Classes/Service/Transformation/ValueMapTransformationService.php:Nawork\\NaworkUri\\Service\\Transformation\\ValueMapTransformationService');
\Nawork\NaworkUri\Utility\GeneralUtility::registerTransformationService('PagePath', 'EXT:nawork_uri/Classes/Service/Transformation/PagePathTransformationService.php:Nawork\\NaworkUri\\Service\\Transformation\\PagePathTransformationService');
\Nawork\NaworkUri\Utility\GeneralUtility::registerTransformationService('Database', 'EXT:nawork_uri/Classes/Service/Transformation/DatabaseTransformationService.php:Nawork\\NaworkUri\\Service\\Transformation\\DatabaseTransformationService');

// register default configuration, but do not override if default is already set
\Nawork\NaworkUri\Utility\GeneralUtility::registerConfiguration('default', 'EXT:nawork_uri/Configuration/Url/DefaultConfiguration.xml', FALSE);

?>
