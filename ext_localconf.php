<?php

$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['linkData-PostProc']['nawork_uri'] = 'EXT:nawork_uri/class.tx_naworkuri.php:&tx_naworkuri->params2cool';
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc']['nawork_uri'] = 'EXT:nawork_uri/class.tx_naworkuri.php:&tx_naworkuri->cool2params';
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['isOutputting']['nawork_uri'] = 'EXT:nawork_uri/class.tx_naworkuri.php:&tx_naworkuri->goForRedirect';

?>
