<?php

namespace Nawork\NaworkUri\Transformation\Plain;

/**
 * Description of PlainTransformationService
 *
 * @author Thorben Kapp <thorben@work.de>
 */
class TransformationService extends \Nawork\NaworkUri\Transformation\AbstractTransformationService {

	/**
	 *
	 * @param TransformationConfiguration                     $configuration
	 * @param mixed                                           $value
	 * @param \Nawork\NaworkUri\Utility\TransformationUtility $transformationUtility
	 *
	 * @return string
	 */
	public function transform($configuration, $value, $transformationUtility) {
		if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($value) || strcmp($configuration->getMath(),
				'')
		) {
			$value = (int)$value;
			list($operator, $operand) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(' ',
				$configuration->getMath());
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

		$wrap = $configuration->getWrap($transformationUtility->getLanguage());
		if (!empty($wrap) && strpos($wrap, '|') !== FALSE) {
			$value = str_replace('|', $value, $wrap);
		}
		return $value;
	}

}

?>