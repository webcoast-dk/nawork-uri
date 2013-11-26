<?php

if (!defined('TYPO3_MODE'))
	die('Access denied.');

$TCA['tx_naworkuri_uri'] = Array(
	'ctrl' => $TCA['tx_naworkuri_uri']['ctrl'],
	'interface' => Array(
		'showRecordFieldList' => 'sys_language_uid,domain,path,params,hash_path,hash_params,locked,type,redirect_path,redirect_mode'
	),
	'feInterface' => $TCA['tx_naworkuri_uri']['feInterface'],
	'columns' => Array(
		'page_uid' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:tx_naworkuri_uri.page',
			'config' => Array(
				'allowed' => 'pages',
				'internal_type' => 'db',
				'maxitems' => 1,
				'minitems' => 0,
				'size' => 1,
				'type' => 'group',
				'wizards' => Array(
					'suggest' => Array(
						'type' => 'suggest'
					)
				)
			)
		),
		'sys_language_uid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
				)
			)
		),
		'domain' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:tx_naworkuri_uri.domain',
			'config' => Array(
				'type' => 'select',
				'foreign_table' => 'sys_domain',
				'foreign_table_where' => 'AND tx_naworkuri_masterDomain=0',
			)
		),
		'path' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:tx_naworkuri_uri.path',
			'config' => Array(
				'type' => 'input',
				'size' => '60',
			)
		),
		'params' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:tx_naworkuri_uri.params',
			'config' => Array(
				'type' => 'input',
				'size' => '30',
			)
		),
		'hash_path' => Array(
			'exclude' => 0,
			'label' => 'Hash Path',
			'config' => Array(
				'type' => 'input',
				'readOnly' => 1,
				'size' => '30',
			)
		),
		'hash_params' => Array(
			'exclude' => 0,
			'label' => 'Hash Params',
			'config' => Array(
				'type' => 'input',
				'readOnly' => 1,
				'size' => '30',
			)
		),
		'locked' => Array(
			'exclude' => '1',
			'label' => 'LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:tx_naworkuri_uri.locked',
			'config' => Array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'type' => array(
			'exclude' => '1',
			'label' => 'LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:tx_naworkuri_uri.type',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:tx_naworkuri_uri.type.0', 0), // normal url
					array('LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:tx_naworkuri_uri.type.1', 1), // old url
					array('LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:tx_naworkuri_uri.type.2', 2), // redirect
				),
				'default' => '2'
			)
		),
		'redirect_path' => array(
			'exclude' => '1',
			'label' => 'LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:tx_naworkuri_uri.redirect_path',
			'config' => array(
				'type' => 'input',
				'size' => '80'
			)
		),
		'redirect_mode' => array(
			'exclude' => '1',
			'label' => 'LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:tx_naworkuri_uri.redirect_mode',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:tx_naworkuri_uri.redirect_mode.301', 301),
					array('LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:tx_naworkuri_uri.redirect_mode.302', 303),
					array('LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:tx_naworkuri_uri.redirect_mode.303', 303),
					array('LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:tx_naworkuri_uri.redirect_mode.307', 307)
				)
			)
		)
	),
	'types' => array(
		'0' => array(
			'showitem' => 'type, sys_language_uid, page_uid, path, params, locked'
		),
		'1' => array(
			'showitem' => 'type, sys_language_uid, page_uid, path'
		),
		'2' => array(
			'showitem' => 'type, path, redirect_path, redirect_mode'
		)
	)
);

/*
 * show domain only in Multidomain Setups
 */
$confArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nawork_uri']);
if ($confArray['MULTIDOMAIN']) {
	$TCA['tx_naworkuri_uri']['types'][0]['showitem'] = 'domain, ' . $TCA['tx_naworkuri_uri']['types'][0]['showitem'];
	$TCA['tx_naworkuri_uri']['types'][1]['showitem'] = 'domain, ' . $TCA['tx_naworkuri_uri']['types'][1]['showitem'];
	$TCA['tx_naworkuri_uri']['types'][2]['showitem'] = 'domain, ' . $TCA['tx_naworkuri_uri']['types'][2]['showitem'];
}
?>
