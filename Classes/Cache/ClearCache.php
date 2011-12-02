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
class Tx_NaworkUri_Cache_ClearCache {
	/**
	 *
	 * @var t3lib_db
	 */
	protected $db;

	public function __construct() {
		$this->db = $GLOBALS['TYPO3_DB'];
	}

	public function clearUrlCache() {
		$this->db->exec_UPDATEquery('tx_naworkuri_uri', 'deleted=0', array('tstamp' => 0), array('tstamp'));
	}
}

?>
