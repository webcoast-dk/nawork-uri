<?php

namespace Nawork\NaworkUri\Transformation\PagePath;
use Nawork\NaworkUri\Transformation\AbstractTransformationService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Description of PagePathTransformationService
 *
 * @author Thorben Kapp <thorben@work.de>
 */
class TransformationService extends AbstractTransformationService {

	/**
	 * @param mixed                                                                 $value
	 * @param \Nawork\NaworkUri\Transformation\PagePath\TransformationConfiguration $configuration
	 * @param \Nawork\NaworkUri\Utility\TransformationUtility                       $transformationUtility
	 *
	 * @return string
	 *
	 * @throws \Exception
	 */
	public function transform($configuration, $value, $transformationUtility) {
		$rootLine = $this->getRootline($value, $configuration->getTable());
        if (count($rootLine) === 0) {
            throw new \RuntimeException('Page path could not be transformed, due to empty rootline.', 1469629524);
        } elseif (count($rootLine) === 1) {
			return '';
		} elseif (count($rootLine) > 1) {
			// reverse root line
			$rootLine = array_reverse($rootLine);
			// remove the first item, the root page
			array_shift($rootLine);
			$transformedValue = array();
			foreach ($rootLine as $pageRecord) {
				if ($transformationUtility->getLanguage() > 0) {
					$translatedFields = $this->getPageOverlay($pageRecord['uid'],
						$configuration,
						$transformationUtility->getLanguage());
					if (count($translatedFields) > 0) {
						ArrayUtility::mergeRecursiveWithOverrule($pageRecord, $translatedFields);
					}
				}
				if (!$pageRecord['tx_naworkuri_exclude']) {
					$fields = GeneralUtility::trimExplode('//', $configuration->getFields(), TRUE);
					array_unshift($fields, 'tx_naworkuri_pathsegment');
					foreach ($fields as $field) {
						if (array_key_exists($field, $pageRecord) && !empty($pageRecord[$field])) {
							$transformedValue[] = $pageRecord[$field];
							break;
						}
					}
				}
			}
			return implode($configuration->getPathSeparator(), $transformedValue);
		}
		throw new \Exception('No rootline could be determined for page with uid "' . $value . '"', 1394656424);
	}

	/**
	 * @param int    $pageUid
	 * @param string $table
	 * @param array  $rootLine
	 *
	 * @return array
	 */
	private function getRootline($pageUid, $table, $rootLine = array()) {
		$pageRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', $table, 'uid=' . intval($pageUid));
		if (is_array($pageRecord)) {
			$rootLine[] = $pageRecord;
			if ($pageRecord['pid'] > 0) {
				return $this->getRootline($pageRecord['pid'], $table, $rootLine);
			}
		}
		return $rootLine;
	}

	/**
	 * @param int                                                                   $pageUid
	 * @param \Nawork\NaworkUri\Transformation\PagePath\TransformationConfiguration $configuration
	 * @param int                                                                   $language
	 *
	 * @return array
	 */
	private function getPageOverlay($pageUid, $configuration, $language) {
		// get overlay fields from conf vars
		$overlayFields = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['pageOverlayFields']);
		$fields = GeneralUtility::trimExplode('//', $configuration->getFields(), TRUE);
		array_unshift($fields, 'tx_naworkuri_pathsegment');
		// merge overlay fields with select fields from transformation configuration
		$overlayFields = array_unique(array_merge($overlayFields, $fields));
		// remove fields that are not present in the current database to avoid sql errors
		$overlayFields = array_intersect($overlayFields,
			array_keys($GLOBALS['TYPO3_DB']->admin_get_fields($configuration->getTranslationTable())));
		$translatedRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(implode(',', $overlayFields),
			$configuration->getTranslationTable(),
			'pid=' . (int)$pageUid . ' AND sys_language_uid=' . (int)$language . ' AND deleted=0 AND hidden=0');
		if (!is_array($translatedRecord)) {
			$translatedRecord = array();
		}
		return $translatedRecord;
	}

}
