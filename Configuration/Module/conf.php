<?php

// DO NOT REMOVE OR CHANGE THESE 3 LINES:
define('TYPO3_MOD_PATH', '../typo3conf/ext/nawork_uri/Configuration/Module/');
//$BACK_PATH = '../../../../../typo3/';
$MCONF['name'] = 'txnaworkuri';
$MCONF['access'] = 'user,group';
$MCONF['script'] = '_DISPATCH';
//$MCONF['icon'] = t3lib_extMgm::extRelPath('nawork_uri').'Resources/Public/Icons/module.png';
//$MCONF['navFrameScript'] = $BACK_PATH . 'alt_db_navframe.php';
$MLANG['default']['tabs_images']['tab'] = '../../Resources/Public/Icons/module.png';
$MLANG['default']['ll_ref'] = 'LLL:EXT:nawork_uri/Resources/Private/Language/locallang_mod_main.xml';


// Default (english) labels:
//$MLANG['default']['tabs']['tab'] = 'n@work URI';
//$MLANG['default']['labels']['tabdescr'] = 'Manage URLs created and used by the n@work URI extension';
//$MLANG['default']['labels']['tablabel'] = 'n@work URI Management';

// German language:
//$MLANG['de']['tabs']['tab'] = 'n@work URI';
//$MLANG['de']['labels']['tabdescr'] = 'Module zum Verwalten der URLs der n@work URI Extension';
//$MLANG['de']['labels']['tablabel'] = 'n@work URI Verwaltung';
?>
