<?php

namespace Nawork\NaworkUri\Configuration;


class ExtensionConfiguration implements \TYPO3\CMS\Core\SingletonInterface {
	/**
	 * This is the directory where the compiled (php code) configuration files
	 * are stored
	 *
	 * @var string
	 */
	protected $configurationCacheDirectory;
	/**
	 * This should be false on production systems, but can be useful on testing
	 * or development systems where there is no valid ssl certificate installed
	 *
	 * @var bool
	 */
	protected $noSslVerify;

	public function __construct() {
		$extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nawork_uri']);
		$this->configurationCacheDirectory = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($extensionConfiguration['configurationCachePath']);
		if (empty($this->configurationCacheDirectory)) {
			$this->configurationCacheDirectory = PATH_site . 'typo3temp/nawork_uri/';
		}
		// make sure the directory ends with a "/"
		if(!substr($this->configurationCacheDirectory, -1) == '/') {
			$this->configurationCacheDirectory .= '/';
		}
		// make sure the directory does exist
		if (!file_exists($this->configurationCacheDirectory)) {
			if (!mkdir($this->configurationCacheDirectory)) {
				throw new \Exception('The configuration cache directory "' . $this->configurationCacheDirectory . '" could not be created', 1394131522);
			}
		} elseif(!is_writable($this->configurationCacheDirectory)) {
			throw new \Exception('The configuration cache directory "'.$this->configurationCacheDirectory.'" is not writable', 1395687097);
		}

		$this->noSslVerify = (bool)$extensionConfiguration['noSslVerify'];
	}

	/**
	 * @return string
	 */
	public function getConfigurationCacheDirectory() {
		return $this->configurationCacheDirectory;
	}

	/**
	 * @return boolean
	 */
	public function getNoSslVerify() {
		return $this->noSslVerify;
	}
}
 