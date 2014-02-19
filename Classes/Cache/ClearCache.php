<?php

namespace Nawork\NaworkUri\Cache;

/**
 * Description of ClearCache
 *
 * @author thorben
 */
class ClearCache {
	/**
	 *
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $db;

	public function __construct() {
		$this->db = $GLOBALS['TYPO3_DB'];
	}

	public function clearUrlCache() {
		$this->db->exec_UPDATEquery('tx_naworkuri_uri', '', array('tstamp' => 0), array('tstamp'));
	}
}

?>
