<?php

namespace Nawork\NaworkUri\Service\Transformation;

/**
 * Description of DatabaseTransformationService
 *
 * @author Thorben Kapp <thorben@work.de>
 */
class DatabaseTransformationService implements \Nawork\NaworkUri\Service\TransformationServiceInterface {

	/**
	 * 
	 * @param \SimpleXMLElement $parameterConfiguration
	 * @param mixed $value
	 * @param \Nawork\NaworkUri\Utility\TransformationUtility $transformationUtility
	 */
	public function transform($parameterConfiguration, $value, $transformationUtility) {
		/* @var $GLOBALS['TYPO3_DB'] \TYPO3\CMS\Core\Database\DatabaseConnection */
		$compareField = (string) $parameterConfiguration->compareField;
		if (empty($compareField))
			$compareField = 'uid';

		$table = (string) $parameterConfiguration->table;
		$replacement = (string) $parameterConfiguration->replacement;

		$selectFields = (string) $parameterConfiguration->selectFields;
		if (empty($selectFields) && preg_match_all('/{(.*?)}/', $replacement, $matches)) {
			// use the matches as select fields
			$selectFields = $matches[1];
		}
		// if select fields are not empty, continue with the transformation
		if (is_array($selectFields) && !empty($selectFields)) {
			$record = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(implode(',', $selectFields), $table, $compareField . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($value, $table));
			if (!is_array($record) || empty($record)) {
				throw new \Nawork\NaworkUri\Exception\TransformationErrorException('The returned record array is empty or not an array');
			}
			$value = $replacement;
			foreach ($record as $key => $field) {
				$value = str_replace('{' . $key . '}', $field, $value);
			}
		}
		return $value;
	}

}

?>