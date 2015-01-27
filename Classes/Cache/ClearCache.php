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
			/* @var $extensionConfiguration \Nawork\NaworkUri\Configuration\ExtensionConfiguration */
			$extensionConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Nawork\\NaworkUri\\Configuration\\ExtensionConfiguration');
			foreach (\TYPO3\CMS\Core\Utility\GeneralUtility::getFilesInDir($extensionConfiguration->getConfigurationCacheDirectory()) as $file) {
				unlink($extensionConfiguration->getConfigurationCacheDirectory() . $file);
			}
		}
	}
}

?>
