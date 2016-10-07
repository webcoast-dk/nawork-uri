<?php

namespace Nawork\NaworkUri\Hooks;


class TceFormsMainFields {
	/**
	 * Set the type, depending on a given get parameter. This gives the ability to set different
	 * defaults, depending on where you come from, e.g. redirect or uri module.
	 *
	 * @param string                             $table
	 * @param string                             $row
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $tceForms
	 */
	public function getMainFields_preProcess($table, &$row, $tceForms) {
		if ($table == 'tx_naworkuri_uri') {
			$editVars = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('edit');
			if (isset($editVars[$table]) && is_array($editVars[$table])) {
				if (array_key_exists('type', $editVars[$table])) {
					$row['type'] = (int) $editVars[$table]['type'];
				}

				if (array_key_exists('page_uid', $editVars[$table]) && (int) $editVars[$table]['page_uid'] > 0) {
					$row['page_uid'] = (int) $editVars[$table]['page_uid'];
				}
			}
		}
	}
}
