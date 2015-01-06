<?php

if (!defined('TYPO3_MODE'))
	die('Access denied.');

$GLOBALS['TCA']['tx_naworkuri_uri'] = array(
	'ctrl' => Array(
		'title' => 'URI',
		'label' => 'path',
		'type' => 'type',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',
		'sortby' => 'crdate',
		'rootLevel' => '-1',
		'enablecolumns' => array(
		),
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('nawork_uri') . 'Resources/Public/Icons/Types/uri.png',
		'hideTable' => true,
		'typeicon_column' => 'type',
		'typeicons' => Array(
			'0' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('nawork_uri') . 'Resources/Public/Icons/Types/uri.png',
			'1' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('nawork_uri') . 'Resources/Public/Icons/Types/old.png',
			'2' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('nawork_uri') . 'Resources/Public/Icons/Types/redirect.png',
		),
	),
	'interface' => Array(
		'showRecordFieldList' => 'sys_language_uid,domain,path,params,hash_path,hash_params,locked,type,redirect_mode'
	),
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
		'redirect_mode' => array(
			'exclude' => '1',
			'label' => 'LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:tx_naworkuri_uri.redirect_mode',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:tx_naworkuri_uri.redirect_mode.301', 301),
					array('LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:tx_naworkuri_uri.redirect_mode.302', 302),
					array('LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:tx_naworkuri_uri.redirect_mode.303', 303),
					array('LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:tx_naworkuri_uri.redirect_mode.307', 307)
				)
			)
		)
	),
	'types' => array(
		'0' => array(
			'showitem' => 'type, domain, sys_language_uid, page_uid, path, params, locked'
		),
		'1' => array(
			'showitem' => 'type, domain, sys_language_uid, page_uid, path'
		),
		'2' => array(
			'showitem' => 'type, domain, path, page_uid, redirect_mode'
		)
	)
);
