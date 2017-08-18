<?php

// hook into typolink to comvert params 2 path
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['linkData-PostProc']['nawork_uri'] = 'EXT:nawork_uri/Classes/Controller/Frontend/UrlController.php:&Nawork\NaworkUri\Controller\Frontend\UrlController->params2uri';
// hook to extract params from path
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc']['nawork_uri'] = 'EXT:nawork_uri/Classes/Controller/Frontend/UrlController.php:&Nawork\NaworkUri\Controller\Frontend\UrlController->uri2params';
// redirect old urls to new
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['configArrayPostProc']['nawork_uri'] = 'EXT:nawork_uri/Classes/Controller/Frontend/UrlController.php:&Nawork\NaworkUri\Controller\Frontend\UrlController->redirect2uri';
$TYPO3_CONF_VARS['SC_OPTIONS']['tce']['formevals']['tx_naworkuri_path'] = 'EXT:nawork_uri/Classes/Validation/class.tx_naworkuri_path.php';

// register transformation services
\Nawork\NaworkUri\Utility\TransformationUtility::registerTransformationService('Hidden', \Nawork\NaworkUri\Transformation\Hidden\TransformationService::class);
\Nawork\NaworkUri\Utility\TransformationUtility::registerTransformationService('Plain', \Nawork\NaworkUri\Transformation\Plain\TransformationService::class);
\Nawork\NaworkUri\Utility\TransformationUtility::registerTransformationService('ValueMap', \Nawork\NaworkUri\Transformation\ValueMap\TransformationService::class);
\Nawork\NaworkUri\Utility\TransformationUtility::registerTransformationService('PagePath', \Nawork\NaworkUri\Transformation\PagePath\TransformationService::class);
\Nawork\NaworkUri\Utility\TransformationUtility::registerTransformationService('Database', \Nawork\NaworkUri\Transformation\Database\TransformationService::class);

// register default configuration, but do not override if default is already set
if (class_exists(Nawork\NaworkUri\Utility\ConfigurationUtility::class)) { // check this to avoid php error when activating the extension
	Nawork\NaworkUri\Utility\ConfigurationUtility::registerConfiguration(
		'default',
		'EXT:nawork_uri/Configuration/Url/DefaultConfiguration.xml',
		FALSE
	);
}

// configure caching framework
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['naworkuri_configuration'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['naworkuri_configuration'] = array();
}
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['naworkuri_configuration']['backend'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['naworkuri_configuration']['backend'] = TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class;
}

// init hook object array
$GLOBALS['TYPO3_CONF_VARS']['EXT']['tx_naworkuri'][Nawork\NaworkUri\Controller\Frontend\UrlController::class] = array();

// add nawork-uri fields to pageOverlayFields
$GLOBALS['TYPO3_CONF_VARS']['FE']['pageOverlayFields'] .= ',tx_naworkuri_pathsegment,tx_naworkuri_exclude';
