<?php

namespace Nawork\NaworkUri\Utility;

use Nawork\NaworkUri\Exception\InvalidConfigurationException;

class ConfigurationUtility {
	/**
	 * @var \Nawork\NaworkUri\Configuration\ConfigurationReader
	 */
	private static $configurationReader;

	public static function getConfigurationFileForCurrentDomain() {
		/** @var \Nawork\NaworkUri\Configuration\TableConfiguration $tableConfiguration */
		$tableConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Nawork\\NaworkUri\\Configuration\\TableConfiguration');
		$file = NULL;
		$domain = 'default';
		$domainUid = GeneralUtility::getCurrentDomain();
		if ($domainUid > 0) {
			$domainRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('domainName', $tableConfiguration->getDomainTable(), 'uid=' . intval($domainUid));
			if (is_array($domainRecord)) {
				$domain = $domainRecord['domainName'];
			}
		}
		try {
			$file = self::findConfigurationByDomain($domain);
		} catch (InvalidConfigurationException $ex) {
			try {
				$file = self::findConfigurationByDomain('default');
			} catch (InvalidConfigurationException $ex) {
			}
		}

		return $file;
	}

	private static function findConfigurationByDomain($domain) {
		if (!array_key_exists($domain, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['configurations'])) {
			throw new InvalidConfigurationException('No configuration for domain \'' . $domain . '\' registered', 1391077835);
		}

		$file = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['configurations'][$domain], TRUE, TRUE);
		if (!file_exists($file) || !is_file($file) && !is_link($file)) {
			throw new InvalidConfigurationException('The configuration file for domain \'' . $domain . '\' does not exist or is not a file/link', 1391077846);
		}

		return $file;
	}

	/**
	 * @return \Nawork\NaworkUri\Configuration\ConfigurationReader
	 */
	public static function getConfigurationReader() {
		if (!self::$configurationReader instanceof \Nawork\NaworkUri\Configuration\ConfigurationReader) {
			self::$configurationReader = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Nawork\\NaworkUri\\Configuration\\ConfigurationReader', self::getConfigurationFileForCurrentDomain());
		}

		return self::$configurationReader;
	}
}

?>