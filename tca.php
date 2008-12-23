<?php
if (!defined ('TYPO3_MODE'))     die ('Access denied.');

$TCA['tx_naworkuri_uri'] = Array (
    'ctrl' => $TCA['tx_naworkuri_uri']['ctrl'],
    'interface' => Array (
        'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,domain,path,params'
    ),
    'feInterface' => $TCA['tx_naworkuri_uri']['feInterface'],
    'columns' => Array (
        'sys_language_uid' => array (        
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
            'config' => array (
                'type' => 'select',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => array(
                    array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages',-1),
                    array('LLL:EXT:lang/locallang_general.xml:LGL.default_value',0)
                )
            )
        ),
        'l18n_parent' => Array (        
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
            'config' => Array (
                'type' => 'select',
                'items' => Array (
                    Array('', 0),
                ),
                'foreign_table' => 'tx_naworkteaser_teaser',
                'foreign_table_where' => 'AND tx_naworkteaser_teaser.pid=###CURRENT_PID### AND tx_naworkteaser_teaser.sys_language_uid IN (-1,0)',
            )
        ),
        'l18n_diffsource' => Array (        
            'config' => Array (
                'type' => 'passthrough'
            )
        ),
        'domain' => Array (        
            'exclude' => 1,        
            'label' => 'URI Domain',        
            'config' => Array (
                'type' => 'input',    
                'size' => '30',
            )
        ),
        'path' => Array (        
            'exclude' => 1,        
            'label' => 'URI Path',        
            'config' => Array (
                'type' => 'input',    
                'size' => '30',
            )
        ),
		'params' => Array (        
            'exclude' => 1,        
            'label' => 'URI Params',        
            'config' => Array (
                'type' => 'input',    
                'size' => '30',
            )
        ),
        'hash_path' => Array (        
            'exclude' => 1,        
            'label' => 'Hash Path',        
            'config' => Array (
                'type' => 'input',
        		'readOnly' => 1,
      			'size' => '30',
        	 )
        ),
        'hash_params' => Array (        
            'exclude' => 1,        
            'label' => 'Hash Params',        
            'config' => Array (
                'type' => 'input',    
        		'readOnly' => 1,
         		'size' => '30',
            )
        ),
    ),
    'types' => Array (
        '0' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource,  path;;;;2-2-2, domain, params, hash_path;;;;3-3-3, hash_params'),
    ),
 
);
?>