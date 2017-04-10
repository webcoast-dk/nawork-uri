<?php

namespace Nawork\NaworkUri\Hooks;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Toolbar\ClearCacheActionsHookInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * Description of ClearCache
 *
 * @author thorben
 */
class ClearCache implements ClearCacheActionsHookInterface {

	/**
	 *
	 * @param array $cacheActions Cache menu items
	 * @param array $optionValues AccessConfigurations-identifiers (typically  used by userTS with options.clearCache.identifier)
	 */
	public function manipulateCacheActions(&$cacheActions, &$optionValues) {
	    /** @var IconFactory $iconFactory */
	    $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
		if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigVal('options.clearCache.urls')) {
			// Add new cache menu item
            if (VersionNumberUtility::convertVersionNumberToInteger(VersionNumberUtility::getCurrentTypo3Version()) >= 8007000) {
                $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                $cacheActions[] = array(
                    'id' => 'clearUrlCache',
                    'title' => 'LLL:EXT:nawork_uri/Resources/Private/Language/locallang_backend.xlf:cache.clearUrlCache',
                    'description' => 'LLL:EXT:nawork_uri/Resources/Private/Language/locallang_backend.xlf:cache.clearUrlCache.description',
                    'href' => $uriBuilder->buildUriFromRoute('ajax_naworkuri_clearUrlCache'),
                    'iconIdentifier' => 'tx-naworkuri'
                );
            } else {
                $cacheActions[] = array(
                    'id' => 'clearUrlCache',
                    'title' => $title = $GLOBALS['LANG']->sL(
                        'LLL:EXT:nawork_uri/Resources/Private/Language/locallang_backend.xlf:cache.clearUrlCache'
                    ),
                    'href' => BackendUtility::getAjaxUrl('tx_naworkuri::clearUrlCache'),
                    'icon' => $iconFactory->getIcon('tx-naworkuri', Icon::SIZE_SMALL)->getMarkup()
                );
            }
			$optionValues[] = 'urls';
		}

		if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigVal('options.clearCache.urlConfiguration')) {
			// Add new cache menu item
            if (VersionNumberUtility::convertVersionNumberToInteger(VersionNumberUtility::getCurrentTypo3Version()) >= 8007000) {
                $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                $cacheActions[] = array(
                    'id' => 'clearUrlConfigurationCache',
                    'title' => 'LLL:EXT:nawork_uri/Resources/Private/Language/locallang_backend.xlf:cache.clearConfigurationCache',
                    'description' => 'LLL:EXT:nawork_uri/Resources/Private/Language/locallang_backend.xlf:cache.clearConfigurationCache.description',
                    'href' => $uriBuilder->buildUriFromRoute('ajax_naworkuri_clearConfigurationCache'),
                    'iconIdentifier' => 'tx-naworkuri'
                );
            } else {
                $cacheActions[] = array(
                    'id' => 'clearUrlConfigurationCache',
                    'title' => $title = $GLOBALS['LANG']->sL(
                        'LLL:EXT:nawork_uri/Resources/Private/Language/locallang_backend.xlf:cache.clearConfigurationCache'
                    ),
                    'href' => BackendUtility::getAjaxUrl('tx_naworkuri::clearUrlConfigurationCache'),
                    'icon' => $iconFactory->getIcon('tx-naworkuri', Icon::SIZE_SMALL)->getMarkup()
                );
            }
			$optionValues[] = 'urlConfiguration';
		}
	}

}
