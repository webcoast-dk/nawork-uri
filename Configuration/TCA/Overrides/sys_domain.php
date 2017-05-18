<?php

use Nawork\NaworkUri\Backend\ItemsProcFunc;

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'sys_domain',
    array(
        'tx_naworkuri_masterDomain' => array(
            'label' => 'LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:sys_domain.tx_naworkuri_masterDomain',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'sys_domain',
                'size' => '1',
                'default' => 0,
                'minitems' => '0',
                'maxitems' => '1',
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'suggest'
                    )
                )
            ),
        ),
        'tx_naworkuri_use_configuration' => [
            'label' => 'LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:sys_domain.tx_naworkuri_use_configuration',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'size' => 1,
                'items' => [
                    ['', '']
                ],
                'itemsProcFunc' => ItemsProcFunc::class . '->sysDomainAlterConfigurationItems'
            ]
        ]
    )
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('sys_domain', '--div--;LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:sys_domain.tabs.nawork_uri, tx_naworkuri_masterDomain, tx_naworkuri_use_configuration');
