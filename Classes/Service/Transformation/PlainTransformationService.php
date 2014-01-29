<?php

namespace Nawork\NaworkUri\Service\Transformation;

/**
 * Description of PlainTransformationService
 *
 * @author Thorben Kapp <thorben@work.de>
 */
class PlainTransformationService implements \Nawork\NaworkUri\Service\TransformationServiceInterface {

	/**
	 * 
	 * @param \SimpleXMLElement $parameterConfiguration
	 * @param mixed $value
	 * @param \Nawork\NaworkUri\Utility\TransformationUtility $transformationUtility
	 * @return string
	 */
	public function transform($parameterConfiguration, $value, $transformationUtility) {
		$math = (string) $parameterConfiguration->math;
		if (!empty($math) && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($value)) {
			// make sure the value is an integer
			$value = intval($value);
			list($operator, $operand) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(' ', $math);
			switch ($operator) {
				case '+':
					$value += $operand;
					break;
				case '-':
					$value -= $operand;
					break;
				case '*':
					$value *= $operand;
					break;
				case '/':
					$value /= $operand;
					break;
			}
		}

		// check for replacement
		$replacement = NULL;
		foreach ($parameterConfiguration->children() as $node) {
			if ($node->getName() == 'replacement') {
				if ($replacement === NULL && !$node->attributes()->language) {
					$replacement = (string) $node;
				}
				if ($node->attributes()->language && intval($node->attributes()->language) === intval($transformationUtility->getLanguage())) {
					$replacement = (string) $node;
				}
			}
		}
		if (!empty($replacement)) {
			$value = str_replace('{value}', $value, $replacement);
		}
		return $value;
	}

}

?>