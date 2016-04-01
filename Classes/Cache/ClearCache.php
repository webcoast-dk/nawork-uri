<?php

namespace Nawork\NaworkUri\Cache;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

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
	 * Removes the generated configuration files, this is needed after the configuration has been changed
	 *
	 * @param mixed $params
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $requestHandler
	 */
	public function clearConfigurationCache(&$params, &$requestHandler) {
		if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigValue('options.clearCacheCmd.urlConfiguration')) {
			/** @var FrontendInterface $cache */
			$cache = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager')->getCache('naworkuri_configuration');
			$cache->flush();
		}
	}
}
