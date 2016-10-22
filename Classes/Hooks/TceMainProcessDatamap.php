<?php

namespace Nawork\NaworkUri\Hooks;


class TceMainProcessDatamap {
	/**
	 * @param array                                    $fields
	 * @param string                                   $table
	 * @param int                                      $id
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $tceMain
	 */
	public function processDatamap_preProcessFieldArray(&$fields, $table, $id, $tceMain) {
		if ($table == 'tx_naworkuri_uri' && is_array($fields)) {
			if (array_key_exists('path', $fields)) {
				$fields['path_hash'] = md5($fields['path']);
			}

			if (array_key_exists('parameters', $fields)) {
				$fields['parameters_hash'] = md5($fields['parameters']);
			}
		}
	}
}
