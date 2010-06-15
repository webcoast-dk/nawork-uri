<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

/*
if (TYPO3_MODE == 'BE')	{
	t3lib_extMgm::addModule('tools','txcooluriM1','',t3lib_extMgm::extPath($_EXTKEY).'mod1/');
}
*/

	// add new fields to page and pages_language_overlay records
$tempColumns = Array( 
	'tx_naworkuri_pathsegment' => array(
		'label' => 'LLL:EXT:nawork_uri/locallang_db.xml:pages.tx_naworkuri_pathsegment',
		'config' => Array (
			'type' => 'input',
			'size' => '60',
			'max' => '60',
		),
	),
	'tx_naworkuri_exclude' => array(
		'label' => 'LLL:EXT:nawork_uri/locallang_db.xml:pages.tx_naworkuri_exclude',
		'config' => Array (
			'type' => 'check',
			'default' => '0' 
		)
	)
); 

t3lib_div::loadTCA('pages');
t3lib_extMgm::addTCAcolumns('pages',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('pages','tx_naworkuri_pathsegment,tx_naworkuri_exclude','','after:title');

t3lib_div::loadTCA('pages_language_overlay');
t3lib_extMgm::addTCAcolumns('pages_language_overlay',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('pages_language_overlay','tx_naworkuri_pathsegment,tx_naworkuri_exclude','','after:title');

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
		'enablecolumns' => Array (
            'disabled' => 'hidden', 
        ), 
		'thumbnail' => 'image',
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'icon_naworkuri_uri.gif',
        'hideAtCopy' => true,
		'hideTable' => true,
        'typeicon_column' => 'sticky',
        'typeicons' => Array ( 
            '0' => t3lib_extMgm::extRelPath($_EXTKEY).'icon_naworkuri_uri.gif',
            '1' => t3lib_extMgm::extRelPath($_EXTKEY).'icon_naworkuri_uri_sticky.gif',
        ), 
        
    ),
    
);	

if (TYPO3_MODE=="BE")	{
	if (TYPO3_MODE=='BE')	{
		t3lib_extMgm::addModule('web','txnaworkuriM1','',t3lib_extMgm::extPath($_EXTKEY).'mod1/');
	}
}


?>
