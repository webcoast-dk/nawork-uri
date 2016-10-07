<?php

namespace Nawork\NaworkUri\Cache;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
		if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigVal('options.clearCache.urls')) {
			$this->db->exec_UPDATEquery('tx_naworkuri_uri', '', array('tstamp' => 0), array('tstamp'));
		}
	}

	/**
	 * Clears the configuration cache, this is needed after the configuration has been changed
	 */
	public function clearConfigurationCache() {
		if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigValue('options.clearCacheCmd.urlConfiguration')) {
			/** @var FrontendInterface $cache */
			$cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('naworkuri_configuration');
			$cache->flush();
		}
	}
}
