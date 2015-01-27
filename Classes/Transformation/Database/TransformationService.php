<?php

namespace Nawork\NaworkUri\Transformation\Database;

/**
 * Description of DatabaseTransformationService
 *
 * @author Thorben Kapp <thorben@work.de>
 */
class TransformationService extends \Nawork\NaworkUri\Transformation\AbstractTransformationService {

	/**
	 * 
	 * @param TransformationConfiguration $configuration
	 * @param mixed $value
	 * @param \Nawork\NaworkUri\Utility\TransformationUtility $transformationUtility
	 *
	 * @return string
	 */
	public function transform($configuration, $value, $transformationUtility) {
		$record = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*',
			$configuration->getTable(),
			$configuration->getCompareField() .
			'=' .
			$GLOBALS['TYPO3_DB']->fullQuoteStr($value,
				$configuration->getTable()));
		if (is_array($record)) {
			if (preg_match_all('/{(.*?)}/',
				$configuration->getPattern($transformationUtility->getLanguage()),
				$fields)
			) {
				if ($transformationUtility->getLanguage() > 0 && // languag must be greater than 0
					is_array($GLOBALS['TCA'][$configuration->getTable()]['ctrl']) && // the table must be configured
					!empty($GLOBALS['TCA'][$configuration->getTable()]['ctrl']['languageField']) && // the language field must be configured
					$record[$GLOBALS['TCA'][$configuration->getTable()]['ctrl']['languageField']] == 0 && // the record must be in original language
					!empty($GLOBALS['TCA'][$configuration->getTable()]['ctrl']['transOrigPointerField']) // the localization parent field must be defined
				) {
					$translatedFields = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(implode(',', $fields[1]),
						$configuration->getTable(),
						$GLOBALS['TCA'][$configuration->getTable()]['ctrl']['languageField'] .
						'=' .
						(int)$transformationUtility->getLanguage() .
						' AND ' .
						$GLOBALS['TCA'][$configuration->getTable()]['ctrl']['transOrigPointerField'] .
						'=' .
						(int)$record['uid']);
					if (is_array($translatedFields)) {
						\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($record, $translatedFields);
					}
				}
				$output = $configuration->getPattern($transformationUtility->getLanguage());
				foreach ($fields[1] as $field) {
					// replace the field placeholder if the field exist in the record
					// if not leave the placeholder to show something is wrong
					if (array_key_exists($field, $record)) {
						$output = str_replace('{' . $field . '}', $record[$field], $output);
					}
				}
				return $output;
			}
		}
		return $value;
	}

}

?>