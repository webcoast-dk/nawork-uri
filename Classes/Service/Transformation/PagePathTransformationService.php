<?php

namespace Nawork\NaworkUri\Service\Transformation;

/**
 * Description of PagePathTransformationService
 *
 * @author Thorben Kapp <thorben@work.de>
 */
class PagePathTransformationService implements \Nawork\NaworkUri\Service\TransformationServiceInterface {

	/**
	 * 
	 * @param \SimpleXMLElement $parameterConfiguration
	 * @param mixed $value
	 * @param \Nawork\NaworkUri\Utility\TransformationUtility $transformationUtility
	 * @return string
	 */
	public function transform($parameterConfiguration, $value, $transformationUtility) {
		/* @var $GLOBALS['TSFE'] \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController */
		/* @var $tableConfiguration \Nawork\NaworkUri\Configuration\TableConfiguration */
		$tableConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Nawork\\NaworkUri\\Configuration\\TableConfiguration');
		$rootline = $this->getRootline($value, (string) $tableConfiguration->getPageTable());

		// only one page (root page) is in the rootline, return an empty path
		if (count($rootline) == 1) {
			return '';
		}

		$pagePathElements = array();
		// explode fields from configuration
		$fields = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('//', (string) $parameterConfiguration->field);
		// prepend path segment field, as it takes precedence, if set
		array_unshift($fields, 'tx_naworkuri_pathsegment');
		foreach ($rootline as $page) {
			if (intval($transformationUtility->getLanguage()) > 0) {
				$translatedPage = $GLOBALS['TSFE']->sys_page->getPageOverlay($page['uid'], intval($transformationUtility->getLanguage()));
				// if we have a translated page record and it is really translated (uids are different)
				if (is_array($translatedPage) && !empty($translatedPage) && $page['uid'] != $translatedPage['uid']) {
					// Do intersection of the original and translated page record
					// with translation as the base to return its values.
					// If a non-integer (in original) field not empty in translation and
					// in original, return 0 to keep it from the intersection.
					$page = array_uintersect_assoc(
						$translatedPage,
						$page,
						function ($translatedFieldValue, $originalFieldValue) {
							if (!empty($translatedFieldValue) && !empty($originalFieldValue)) {
								return 0;
							}

							return 1;
						}
					);
				}
			}
			// if the page should not be excluded from the page path
			if (intval($page['tx_naworkuri_exclude']) < 1) {
				// look which field is filled first and add it to the
				// path elements array
				foreach ($fields as $fieldname) {
					if (!empty($page[$fieldname])) {
						$pagePathElements[] = $page[$fieldname];
						break;
					}
				}
			}
		}
		// build page path
		if (count($pagePathElements > 1)) {
			// remove first item (root page)
			array_shift($pagePathElements);
			// evaluate the path seperator
			$pagePathSeperator = (string) $parameterConfiguration->pathSeperator;
			if (empty($pagePathSeperator)) {
				$pagePathSeperator = '/';
			}
			// and concatenate the path elements to a path
			$value = implode($pagePathSeperator, $pagePathElements);
		}

		return $value;
	}

	private function getRootline($pageUid, $table, &$rootline = array()) {
		/* @var $GLOBALS['TYPO3_DB'] \TYPO3\CMS\Core\Database\DatabaseConnection */
		$pageRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', $table, 'uid=' . intval($pageUid));
		array_unshift($rootline, $pageRecord);
		if (intval($pageRecord['pid']) > 0) {
			$this->getRootline($pageRecord['pid'], $table, $rootline);
		}
		return $rootline;
	}

}

?>