<?php

namespace Nawork\NaworkUri\ContextMenu;


use TYPO3\CMS\Backend\ContextMenu\ItemProviders\AbstractProvider;
use TYPO3\CMS\Backend\Utility\BackendUtility;

class ItemProvider extends AbstractProvider
{
    protected $itemsConfiguration = [
        'lock' => [
            'label' => 'LLL:EXT:nawork_uri/Resources/Private/Language/locallang.xlf:clickMenu.lock',
            'iconIdentifier' => 'action-url-lock',
            'callbackAction' => 'lockUrl',
            'additionalAttributes' => ['data-callback-module' => 'TYPO3/CMS/NaworkUri/ContextMenuActions']
        ],
        'unlock' => [
            'label' => 'LLL:EXT:nawork_uri/Resources/Private/Language/locallang.xlf:clickMenu.unlock',
            'iconIdentifier' => 'action-url-unlock',
            'callbackAction' => 'unlockUrl',
            'additionalAttributes' => ['data-callback-module' => 'TYPO3/CMS/NaworkUri/ContextMenuActions']
        ],
        'divider1' => [
            'type' => 'divider'
        ],
        'edit' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:cm.edit',
            'iconIdentifier' => 'actions-open',
            'callbackAction' => 'editRecord'
        ],
        'divider2' => [
            'type' => 'divider'
        ],
        'delete' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:cm.delete',
            'iconIdentifier' => 'actions-edit-delete',
            'callbackAction' => 'delete',
            'additionalAttributes' => ['data-callback-module' => 'TYPO3/CMS/NaworkUri/ContextMenuActions']
        ],
        'deleteSelected' => [
            'label' => 'LLL:EXT:nawork_uri/Resources/Private/Language/locallang.xlf:clickMenu.deleteSelected',
            'iconIdentifier' => 'actions-edit-delete',
            'callbackAction' => 'deleteSelected',
            'additionalAttributes' => ['data-callback-module' => 'TYPO3/CMS/NaworkUri/ContextMenuActions']
        ]
    ];

    /**
     * @return bool
     */
    public function canHandle(): bool
    {
        return $this->table === 'tx_naworkuri_uri';
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return 40;
    }

    /**
     * Checks whether certain item can be rendered (e.g. check for disabled items or permissions)
     *
     * @param string $itemName
     * @param string $type
     *
     * @return bool
     */
    protected function canRender(string $itemName, string $type): bool
    {
        if (in_array($itemName, $this->disabledItems)) {
            return false;
        }

        if ($this->backendUser->check('tables_modify', $this->table)) {
            if ($itemName === 'lock' || $itemName === 'unlock') {
                $record = BackendUtility::getRecord($this->table, $this->identifier);
                if ((int)$record['type'] !== 0) {
                    return false;
                }

                return ($itemName === 'lock') ? (int)$record['locked'] === 0 : (int)$record['locked'] === 1;
            } elseif ($itemName === 'deleteSelected') {
                // only show "delete selected" option if there are multiple records selected
                return $this->context === 'multiple';
            }

            return true;
        }

        return false;
    }

    public function addItems(array $items): array
    {
        return $this->prepareItems($this->itemsConfiguration);
    }

    protected function getAdditionalAttributes(string $itemName): array
    {
        return (isset($this->itemsConfiguration[$itemName]['additionalAttributes']) ? $this->itemsConfiguration[$itemName]['additionalAttributes'] : []);
    }
}
