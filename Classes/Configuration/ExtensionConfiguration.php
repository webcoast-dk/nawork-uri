<?php

namespace Nawork\NaworkUri\Configuration;


use TYPO3\CMS\Core\SingletonInterface;

class ExtensionConfiguration implements SingletonInterface {
	/**
	 * This should be false on production systems, but can be useful on testing
	 * or development systems where there is no valid ssl certificate installed
	 *
	 * @var bool
	 */
	protected $noSslVerify;

	public function __construct() {
		$extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nawork_uri']);
		$this->noSslVerify = (bool)$extensionConfiguration['noSslVerify'];
	}

	/**
	 * @return boolean
	 */
	public function getNoSslVerify() {
		return $this->noSslVerify;
	}
}
