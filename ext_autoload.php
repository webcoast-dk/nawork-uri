<?php
$extPath = t3lib_extMgm::extPath('nawork_uri');
$libsPath = t3lib_extMgm::extPath('nawork_uri').'lib/';

return array(
	'tx_naworkuri_configreader' => $libsPath.'class.tx_naworkuri_configReader.php',
	'tx_naworkuri_basic_tc' => $libsPath.'../tests/class.tx_naworkuri_basic_tc.php',
	'tx_naworkuri_transformer' => $libsPath.'class.tx_naworkuri_transformer.php',
	'tx_naworkuri_exception_urlisredirectexception' => $extPath.'Classes/Exception/Tx_NaworkUri_Exception_UrlIsRedirectException.php'
);

?>
