<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of DomainItemFunc
 *
 * @author thorben
 */
class Tx_NaworkUri_UserFunc_DomainItemFunc {

	/**
	 *
	 * @param array $params
	 * @param t3lib_TCEforms $pObj
	 */
	public function drawForm($params, $pObj) {
		global $TCA;
		$currentValue = 0;
		$noMatchingValue = TRUE;
		$urls = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('domain', 'tx_naworkuri_uri', 'uid=' . intval($params['row']['uid']), '', '', 1);
		if (is_array($urls) && count($urls) > 0) {
			$domains = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', 'sys_domain', 'domainName=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($urls[0]['domain'], 'sys_domain'), '', '', 1);
			if (is_array($domains) && count($domains) > 0) {
				$currentValue = $domains[0]['uid'];
			}
		}
		$allDomains = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,' . $TCA[$params['fieldConf']['config']['foreign_table']]['ctrl']['label'], $params['fieldConf']['config']['foreign_table'], 'hidden=0 ' . $params['fieldConf']['config']['foreign_table_where']);
		$hiddenInput = '<input type="hidden" value="' . $currentValue . '" name="' . $params['itemFormElName'] . '_selIconVal" />';
		$selectBegin = '<select onchange="if (this.options[this.selectedIndex].value==\'--div--\') {this.selectedIndex=0;} TBE_EDITOR.fieldChanged(\'' . $params['table'] . '\',\'' . $params['row']['uid'] . '\',\'' . $params['field'] . '\',\'' . $params['itemFormElName'] . '\');" class="select" name="' . $params['itemFormElName'] . '" id="' . uniqid('tceforms-select-') . '">';
		$selectEnd = '</select>';
		$options = array();
		foreach ($allDomains as $domain) {
			if ($domain['uid'] == $currentValue) {
				$noMatchingValue = FALSE;
			}
			$options[] = '<option value="' . $domain['uid'] . '" ' . ($domain['uid'] == $currentValue ? 'selected="selected" ' : '') . '>' . $domain[$GLOBALS['TCA'][$params['fieldConf']['config']['foreign_table']]['ctrl']['label']] . '</option>';
		}
		// No-matching-value:
		if ($currentValue && $noMatchingValue && !$params['fieldTSConfig']['disableNoMatchingValueElement'] && !$params['fieldConf']['config']['disableNoMatchingValueElement']) {
			// Creating the label for the "No Matching Value" entry.
			$nMV_label = isset($PA['fieldTSConfig']['noMatchingValue_label']) ? $pObj->sL($PA['fieldTSConfig']['noMatchingValue_label']) : '[ ' . $pObj->getLL('l_noMatchingValue') . ' ]';
			$nMV_label = @sprintf($nMV_label, $currentValue);
			$options[] = '<option value="' . htmlspecialchars($currentValue) . '" selected="selected">' . htmlspecialchars($nMV_label) . '</option>';
		}
		return $hiddenInput . $selectBegin . implode("\n", $options) . $selectEnd;
	}

}

?>
