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
                'foreign_table' => 'tx_naworkuri_uri',
                'foreign_table_where' => 'AND tx_naworkuri_uri.pid=###CURRENT_PID### AND tx_naworkuri_uri.sys_language_uid IN (-1,0)',
            )
        ),
        'l18n_diffsource' => Array (
            'config' => Array (
                'type' => 'passthrough'
            )
        ),
        'domain' => Array (
            'exclude' => 1,
            'label' => 'LLL:EXT:nawork_uri/Resources/Language/locallang_db.xml:tx_naworkuri_uri.domain',
            'config' => Array (
                'type' => 'input',
                'size' => '30',
            )
        ),
        'path' => Array (
            'exclude' => 1,
            'label' => 'LLL:EXT:nawork_uri/Resources/Language/locallang_db.xml:tx_naworkuri_uri.path',
            'config' => Array (
                'type' => 'input',
                'size' => '60',
            )
        ),
		'params' => Array (
            'exclude' => 1,
            'label' => 'LLL:EXT:nawork_uri/Resources/Language/locallang_db.xml:tx_naworkuri_uri.params',
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
        'debug_info' => Array (
            'exclude' => 1,
            'label' => 'debug_info',
            'config' => Array (
                'type' => 'text',
                'cols' => '50',
        		'rows' => 5,
            )
        ),
        'sticky' => Array (
        	'label' => 'LLL:EXT:nawork_uri/Resources/Language/locallang_db.xml:tx_naworkuri_uri.locked',
        	'config' => Array (
        		'type' => 'check',
				'default' => '0'
        	)
        ),
		'type' => array(
			'label' => 'LLL:EXT:nawork_uri/Resources/Language/locallang_db.xml:tx_naworkuri_uri.type',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:nawork_uri/Resources/Language/locallang_db.xml:tx_naworkuri_uri.type.0', 0), // normal url
					array('LLL:EXT:nawork_uri/Resources/Language/locallang_db.xml:tx_naworkuri_uri.type.1', 1), // old url
					array('LLL:EXT:nawork_uri/Resources/Language/locallang_db.xml:tx_naworkuri_uri.type.2', 2), // redirect
				),
				'default' => '2'
			)
		),
		'redirect_path' => array(
			'label' => 'LLL:EXT:nawork_uri/Resources/Language/locallang_db.xml:tx_naworkuri_uri.redirect_path',
			'config' => array(
				'type' => 'input',
				'size' => '80'
			)
		),
		'redirect_mode' => array(
			'label' => 'LLL:EXT:nawork_uri/Resources/Language/locallang_db.xml:tx_naworkuri_uri.redirect_mode',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:nawork_uri/Resources/Language/locallang_db.xml:tx_naworkuri_uri.redirect_mode.301', 301),
					array('LLL:EXT:nawork_uri/Resources/Language/locallang_db.xml:tx_naworkuri_uri.redirect_mode.307', 307)
				)
			)
		)
    ),
	'types' => array(
		'0' => array(
			'showitem' => 'domain, path, params, sticky'
		),
		'1' => array(
			'showitem' => 'domain, path'
		),
		'2' => array(
			'showitem' => 'domain, path, redirect_path, redirect_mode'
		)
	)
);

// show domain only in Multidomain Setups
//$confArray = unserialize( $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nawork_uri']);
//if ($confArray['MULTIDOMAIN']){
//	$TCA['tx_naworkuri_uri']['types'] = Array (
//        '0' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource,  path;;;;2-2-2, domain, params, hash_path;;;;3-3-3, hash_params, sticky, hidden, debug_info'),
//    );
//} else {
//	$TCA['tx_naworkuri_uri']['types'] = Array (
//        '0' => Array('showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource,  path;;;;2-2-2, params, hash_path;;;;3-3-3, hash_params, sticky, hidden, debug_info'),
//    );
//}

?>