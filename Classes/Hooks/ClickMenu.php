<?php

namespace Nawork\NaworkUri\Hooks;


use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

class ClickMenu implements SingletonInterface
{
    public function main(\TYPO3\CMS\Backend\ClickMenu\ClickMenu $clickMenu, $items, $table, $uid)
    {
        if ($table === 'tx_naworkuri_uri') {
            /** @var LanguageService $languageService */
            $languageService = GeneralUtility::makeInstance(LanguageService::class);
            /** @var IconFactory $iconFactory */
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            $record = BackendUtility::getRecord($table, $uid);
            if ((int)$record['type'] === 0) {
                if ((int)$record['locked'] === 0) {
                    $label = $languageService->sL(
                        'LLL:EXT:nawork_uri/Resources/Private/Language/locallang.xlf:clickMenu.lock',
                        true
                    );
                    $icon = $iconFactory->getIcon('action-url-lock', Icon::SIZE_SMALL);
                    $onclick = '$(\'.urlTable\').trigger(\'lock\', [\'' . $uid . '\']);';
                    $items = $clickMenu->addMenuItems($items, ['lock' => $clickMenu->linkItem($label, $icon->getMarkup(), $onclick, 1)], 'after-spacer:info');
                } else {
                    $label = $languageService->sL(
                        'LLL:EXT:nawork_uri/Resources/Private/Language/locallang.xlf:clickMenu.unlock',
                        true
                    );
                    $icon = $iconFactory->getIcon('action-url-unlock', Icon::SIZE_SMALL);
                    $onclick = '$(\'.urlTable\').trigger(\'unlock\', [\'' . $uid . '\']);';
                    $items = $clickMenu->addMenuItems($items, ['unlock' => $clickMenu->linkItem($label, $icon->getMarkup(), $onclick, 1)], 'after-spacer:info');
                }
            }

            // change normal delete option
            $items['delete'] = $clickMenu->linkItem($items['delete'][1], $items['delete'][2], '$(\'.urlTable\').trigger(\'delete\', \'' . $uid . '\');', $items['delete'][4], $items['delete'][5]);

            // add delete selected item
            $label = $languageService->sL(
                'LLL:EXT:nawork_uri/Resources/Private/Language/locallang.xlf:clickMenu.deleteSelected', true
            );
            $icon = $iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL);
            $onclick = '$(\'.urlTable\').trigger(\'deleteSelected\');';
            $items = $clickMenu->addMenuItems($items, ['deleteSelected' => $clickMenu->linkItem($label, $icon->getMarkup(), $onclick, 1)], 'after:delete');
        }

        return $items;
    }
}