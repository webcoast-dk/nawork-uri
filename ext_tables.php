<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE == 'BE')	{
		
	t3lib_extMgm::addModule('tools','txcooluriM1','',t3lib_extMgm::extPath($_EXTKEY).'mod1/');
}

$TCA['pages']['columns']['tx_realurl_pathsegment'] = array(
	'label' => 'LLL:EXT:cooluri/locallang_db.php:pages.tx_cooluri_pathsegment',
	'config' => Array (
		'type' => 'input',
		'size' => '30',
		'max' => '30',
	)
); 

$TCA['pages']['columns']['tx_cooluri_exclude'] = array(
	'label' => 'LLL:EXT:cooluri/locallang_db.php:pages.tx_cooluri_exclude',
	'config' => Array (
		'type' => 'check',
		'default' => '0' 
	)
); 

t3lib_extMgm::addToAllTCAtypes('pages','tx_realurl_pathsegment','2','after:nav_title');
t3lib_extMgm::addToAllTCAtypes('pages','tx_cooluri_exclude');

?>
