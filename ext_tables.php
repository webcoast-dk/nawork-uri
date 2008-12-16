<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

/*
if (TYPO3_MODE == 'BE')	{
	t3lib_extMgm::addModule('tools','txcooluriM1','',t3lib_extMgm::extPath($_EXTKEY).'mod1/');
}
*/

	// add new fields to page records
$tempColumns = Array( 
	'tx_realurl_pathsegment' => array(
		'label' => 'LLL:EXT:cooluri/locallang_db.php:pages.tx_cooluri_pathsegment',
		'config' => Array (
			'type' => 'input',
			'size' => '30',
			'max' => '30',
		),
	),
	'tx_cooluri_exclude' => array(
		'label' => 'LLL:EXT:cooluri/locallang_db.php:pages.tx_cooluri_exclude',
		'config' => Array (
			'type' => 'check',
			'default' => '0' 
		)
	)
); 
t3lib_div::loadTCA('pages');
t3lib_extMgm::addTCAcolumns('pages',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('pages','tx_realurl_pathsegment','2','after:nav_title');
t3lib_extMgm::addToAllTCAtypes('pages','tx_cooluri_exclude');

	// add URI-Records
t3lib_extMgm::allowTableOnStandardPages('tx_naworkuri_uri');
$TCA['tx_naworkuri_uri'] = Array (
    'ctrl' => Array (
        'title' => 'URI',      
        'label' => 'path',
		'type' => '',   
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'languageField' => 'sys_language_uid',    
        'transOrigPointerField' => 'l18n_parent',    
        'transOrigDiffSourceField' => 'l18n_diffsource',    
        'sortby' => 'crdate',    
        'delete' => 'deleted',    
		'thumbnail' => 'image',
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'icon_naworkuri_uri.gif',
    ),
    'feInterface' => Array ()
);	


?>
