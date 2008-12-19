<?php

	// hook into typolink to comvert params 2 path
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['linkData-PostProc']['nawork_uri'] = 'EXT:nawork_uri/class.tx_naworkuri.php:&tx_naworkuri->params2cool';
	// hook to extract params from path
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc']['nawork_uri'] = 'EXT:nawork_uri/class.tx_naworkuri.php:&tx_naworkuri->cool2params';
	// redirect old urls to new
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['isOutputting']['nawork_uri'] = 'EXT:nawork_uri/class.tx_naworkuri.php:&tx_naworkuri->goForRedirect';

	//@TODO add a tce_main hook to create the path and parameter hashes automatically

 
?>
