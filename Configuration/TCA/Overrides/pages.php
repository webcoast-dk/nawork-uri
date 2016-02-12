<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'pages',
    array(
        'tx_naworkuri_pathsegment' => array(
            'label' => 'LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:pages.tx_naworkuri_pathsegment',
            'config' => Array(
                'type' => 'input',
                'size' => '60',
                'max' => '60',
            ),
        ),
        'tx_naworkuri_exclude' => array(
            'label' => 'LLL:EXT:nawork_uri/Resources/Private/Language/locallang_db.xml:pages.tx_naworkuri_exclude',
            'config' => Array(
                'type' => 'check',
                'default' => '0'
            )
        )
    )
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    'tx_naworkuri_pathsegment,tx_naworkuri_exclude',
    '',
    'after:title'
);
