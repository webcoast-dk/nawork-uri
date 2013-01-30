<?php

// DO NOT REMOVE OR CHANGE THESE 3 LINES:
define('TYPO3_MOD_PATH', '../typo3conf/ext/nawork_uri/Configuration/Module/');
$BACK_PATH = '../../../../../typo3/';
$MCONF['name'] = 'txnaworkuriM1';
$MCONF['access'] = 'user,group';
$MCONF['script'] = '__DISPATCH';
$MCONF['navFrameScript'] = $BACK_PATH . 'alt_db_navframe.php';
$MLANG['default']['tabs_images']['tab'] = 'moduleicon.png';
$MLANG['default']['ll_ref'] = 'LLL:EXT:nawork_uri/Resources/Language/locallang_mod_main.xml';


// Default (english) labels:
$MLANG['default']['tabs']['tab'] = 'n@work URI';
$MLANG['default']['labels']['tabdescr'] = 'Manage URLs created and used by the n@work URI extension';
$MLANG['default']['labels']['tablabel'] = 'n@work URI Management';

// German language:
$MLANG['de']['tabs']['tab'] = 'n@work URI';
$MLANG['de']['labels']['tabdescr'] = 'Module zum Verwalten der URLs der n@work URI Extension';
$MLANG['de']['labels']['tablabel'] = 'n@work URI Verwaltung';
?>
