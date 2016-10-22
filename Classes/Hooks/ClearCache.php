<?php

namespace Nawork\NaworkUri\Hooks;
use TYPO3\CMS\Backend\Toolbar\ClearCacheActionsHookInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
			$cacheActions[] = array(
				'id' => 'clearUrlCache',
				'title' => $title = $GLOBALS['LANG']->sL('LLL:EXT:nawork_uri/Resources/Private/Language/locallang_backend.xlf:cache.clearUrlCache'),
				'href' => BackendUtility::getAjaxUrl('tx_naworkuri::clearUrlCache'),
				'icon' => $iconFactory->getIcon('tx-naworkuri', Icon::SIZE_SMALL)->getMarkup()
			);
			$optionValues[] = 'urls';
		}

		if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigVal('options.clearCache.urlConfiguration')) {
			// Add new cache menu item
			$cacheActions[] = array(
				'id' => 'clearUrlConfigurationCache',
				'title' => $title = $GLOBALS['LANG']->sL('LLL:EXT:nawork_uri/Resources/Private/Language/locallang_backend.xlf:cache.clearConfigurationCache'),
				'href' => BackendUtility::getAjaxUrl('tx_naworkuri::clearUrlConfigurationCache'),
				'icon' => $iconFactory->getIcon('tx-naworkuri', Icon::SIZE_SMALL)->getMarkup()
			);
			$optionValues[] = 'urlConfiguration';
		}
	}

}
