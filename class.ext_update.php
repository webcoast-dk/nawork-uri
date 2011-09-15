<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ext_update
 *
 * @author thorben
 */
class ext_update {
	const TX_NAWORKURI_UPDATE_MODE_OLD = 0;
	const TX_NAWORKURI_UPDATE_MODE_COOLURI = 1;

	protected $numRecords = 0;
	protected $mode = 0;
	protected $coolUriWasInstalled = FALSE;

	public function main() {
		$this->init();
		$output = '<div>';
		$numRecords = $this->countRecords();
		if (t3lib_div::_GP('doUpdate') > 0) {
			switch ($this->mode) {
				case ext_update::TX_NAWORKURI_UPDATE_MODE_COOLURI:
					break;
				default:
					$GLOBALS['TYPO3_DB']->sql_query('UPDATE tx_naworkuri_uri SET page_uid=pid, locked=sticky WHERE pid>0 AND deleted=0');
					$GLOBALS['TYPO3_DB']->sql_query('UPDATE tx_naworkuri_uri SET pid=0 WHERE pid>0');
					$output = t3lib_div::getIndpEnv(TYPO3_REQUEST_URL);
					header('Location: ' . t3lib_div::getIndpEnv(TYPO3_REQUEST_URL));
			}
		} else {
			$output .= $this->renderModeForm();
			$output .= $this->renderHeader();
			$output .= $this->renderStatus();
		}
		$output .= '</div>';
		return $output;
	}

	private function init() {
		if (t3lib_div::_GP('tx_naworkuri_update_mode') == 'cooluri') {
			$this->mode = ext_update::TX_NAWORKURI_UPDATE_MODE_COOLURI;
		}
		$this->numRecords = $this->countRecords();
		$this->checkIfCoolUriWasInstalled();
	}

	private function countRecords() {
		switch ($this->mode) {
			case ext_update::TX_NAWORKURI_UPDATE_MODE_COOLURI:
				break;
			default:
				return $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', 'tx_naworkuri_uri', '(pid > 0 OR (page_uid=0 OR (sticky=1 AND locked=0))) AND deleted=0');
				break;
		}
	}

	private function renderModeForm() {
		$output = '';
		if ($this->coolUriWasInstalled) {
			$output .= '<form action="" method="post">
				<label for="tx_naworkuri_update_mode">Mode:</label>
				<select id="tx_naworkuri_update_mode" name="tx_naworkuri_update_mode">
				<option value="old_version">Update from an old version of nawork_uri</option>
				<option value="cooluri">Update from cooluri</option>
				</select>
				<input type="submit" value="Check"
				</form>';
		}
		return $output;
	}

	private function renderStatus() {
		$output = '';
		switch ($this->mode) {
			case ext_update::TX_NAWORKURI_UPDATE_MODE_COOLURI:
				break;
			default:
				if ($this->numRecords > 0) {
					$output .= '<p>There are ' . $this->numRecords . ' records that need to be updated!<p>';
					$output .= '<form action="" method="post">';
					$output .= '<input type="hidden" name="doUpdate" value="1" />';
					$output .= '<input type="submit" value="Update!" />';
					$output .= '</form>';
				} else {
					$output .= '<p>URL records are up-to-date! There is nothing to do!</p>';
				}
				break;
		}
		return $output;
	}

	private function renderHeader() {
		switch ($this->mode) {
			case ext_update::TX_NAWORKURI_UPDATE_MODE_COOLURI:
				return '<h2>Update from an CoolURI installation</h2>';
				break;
			default:
				return '<h2>Update from an old nawork_uri version</h2>';
		}
	}

	private function checkIfCoolUriWasInstalled() {
		if (is_dir(PATH_site . 'typo3conf/ext/cooluri')) {
			$res = $GLOBALS['TYPO3_DB']->sql_query('SHOW TABLES');
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res)) {
				if ($row[0] == 'link_cache') {
					$this->coolUriWasInstalled = TRUE;
				}
			}
		}
	}

	/**
	 * Checks how many rows are found and returns true if there are any
	 * (this function is called from the extension manager)
	 *
	 * @param	string		$what: what should be updated
	 * @return	boolean
	 */
	function access($what = 'all') {
		return TRUE;
	}

}

?>
