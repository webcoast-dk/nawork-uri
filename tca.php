<?php
if (!defined ('TYPO3_MODE'))     die ('Access denied.');

$TCA['tx_naworkuri_uri'] = Array (
    'ctrl' => $TCA['tx_naworkuri_uri']['ctrl'],
    'interface' => Array (
        'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource'
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
        'starttime' => array (        
            'exclude' => 1,
            'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.starttime',
            'config'  => array (
                'type'     => 'input',
                'size'     => '8',
                'max'      => '20',
                'eval'     => 'date',
                'default'  => '0',
                'checkbox' => '0'
            )
        ),
        'endtime' => array (        
            'exclude' => 1,
            'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.endtime',
            'config'  => array (
                'type'     => 'input',
                'size'     => '8',
                'max'      => '20',
                'eval'     => 'date',
                'checkbox' => '0',
                'default'  => '0',
                'range'    => array (
                    'upper' => mktime(0, 0, 0, 12, 31, 2020),
                    'lower' => mktime(0, 0, 0, date('m')-1, date('d'), date('Y'))
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
                'size' => '30',
            )
        ),
        'hash_params' => Array (        
            'exclude' => 1,        
            'label' => 'Hash Path',        
            'config' => Array (
                'type' => 'input',    
                'size' => '30',
            )
        ),
    ),
    'types' => Array (
        '0' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, path, params, hash_path, hash_params'),
    ),
 
);
?>