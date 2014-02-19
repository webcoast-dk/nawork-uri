<?php

namespace Nawork\NaworkUri\Hooks;

/**
 * Description of ClearCache
 *
 * @author thorben
 */
class ClearCache implements \TYPO3\CMS\Backend\Toolbar\ClearCacheActionsHookInterface {

	/**
	 *
	 * @param array $cacheActions Cache menu items
	 * @param array $optionValues AccessConfigurations-identifiers (typically  used by userTS with options.clearCache.identifier)
	 */
	public function manipulateCacheActions(&$cacheActions, &$optionValues) {
		if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigVal('options.clearCache.clearUrlCache')) {
			// Add new cache menu item
			$cacheActions[] = array(
				'id' => 'clearUrlCache',
				'title' => $title = $GLOBALS['LANG']->sL('LLL:EXT:nawork_uri/Resources/Private/Language/locallang.xml:label.clearUrlCache'),
				'href' => $GLOBALS['BACK_PATH'] . 'ajax.php?ajaxID=tx_naworkuri::clearUrlCache',
				'icon' => '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], '../typo3conf/ext/nawork_uri/Resources/Public/Icons/module.png', 'width="16" height="16"') . ' title="' . $title . '" alt="' . $title . '" />'
			);
			$optionValues[] = 'clearUrlCache';
		}
	}

}

?>
