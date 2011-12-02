<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ClearCache
 *
 * @author thorben
 */
class Tx_NaworkUri_Cache_ClearCacheMenu implements backend_cacheActionsHook {

	/**
	 *
	 * @param array $cacheActions Cache menu items
	 * @param array $optionValues AccessConfigurations-identifiers (typically  used by userTS with options.clearCache.identifier)
	 */
	public function manipulateCacheActions(&$cacheActions, &$optionValues) {
		if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigVal('options.clearCache.clearUrlCache')) {
			// Add new cache menu item
			$title = $GLOBALS['LANG']->sL('LLL:EXT:nawork_uri/Resources/Language/locallang.xml:label.clearUrlCache');
			$cacheActions[] = array(
				'id' => 'clearUrlCache',
				'title' => $title,
				'href' => $GLOBALS['BACK_PATH'] . 'ajax.php?ajaxID=tx_naworkuri::clearUrlCache',
				'icon' => '<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], '../typo3conf/ext/nawork_uri/mod1/moduleicon.png', 'width="16" height="16"') . ' title="' . $title . '" alt="' . $title . '" />'
			);
			$optionValues[] = 'clearUrlCache';
		}
	}

}

?>
