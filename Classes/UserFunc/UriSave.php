<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of DomainUserFunc
 *
 * @author thorben
 */
class Tx_NaworkUri_UserFunc_UriSave {

	/**
	 * Convert the domain id from the be form to the domain name to store in the uri table
	 *
	 * @param string $status "new" oder "update"
	 * @param string $table The table the records belongs to
	 * @param int $id The uid of the edited record
	 * @param array $fieldArray The array of fields that has changed
	 * @param t3lib_TCEmain $tceMain A reference to the TCEmain object
	 */
	public function processDatamap_postProcessFieldArray($status, $table, $id, &$fieldArray, &$tceMain) {
		if ($table == 'tx_naworkuri_uri') {
			if (array_key_exists('domain', $fieldArray) && t3lib_div::testInt($fieldArray['domain'])) {
				$domain = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'sys_domain', 'uid=' . intval($fieldArray['domain']));
				if (is_array($domain)) {
					$fieldArray['domain'] = $domain['domainName'];
				}
			}
		}
	}

}

?>
