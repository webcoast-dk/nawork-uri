<?php

$extPath = t3lib_extMgm::extPath('nawork_uri');
$libsPath = t3lib_extMgm::extPath('nawork_uri') . 'lib/';

var_dump(PATH_typo3);

return array(
	'tx_naworkuri_configreader' => $libsPath . 'class.tx_naworkuri_configReader.php',
	'tx_naworkuri_basic_tc' => $libsPath . '../tests/class.tx_naworkuri_basic_tc.php',
	'tx_naworkuri_transformer' => $libsPath . 'class.tx_naworkuri_transformer.php',
	'tx_naworkuri_exception_urlisredirectexception' => $extPath . 'Classes/Exception/UrlIsRedirectException.php',
	'tx_naworkuri_exception_urlisnotuniqueexception' => $extPath . 'Classes/Exception/UrlIsNotUniqueException.php',
	'tx_naworkuri_exception_transformationvaluenotfoundexception' => $extPath . 'Classes/Exception/TransformationValueNotFoundException.php',
	'tx_naworkuri_exception_dberrorexception' => $extPath . 'Classes/Exception/DbErrorException.php',
	'tx_naworkuri_path' => $extPath . 'Classes/Validation/class.tx_naworkuri_path.php',
	'tx_naworkuri_cache_transformationcache' => $extPath . 'Classes/Cache/TransformationCache.php',
	'backend_cacheactionshook' => PATH_typo3 . 'interfaces/interface.backend_cacheActionsHook.php',
);
?>
