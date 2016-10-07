<?php

namespace Nawork\NaworkUri\Transformation\Plain;
use Nawork\NaworkUri\Transformation\AbstractTransformationService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Description of PlainTransformationService
 *
 * @author Thorben Kapp <thorben@work.de>
 */
class TransformationService extends AbstractTransformationService {

	/**
	 *
	 * @param TransformationConfiguration                     $configuration
	 * @param mixed                                           $value
	 * @param \Nawork\NaworkUri\Utility\TransformationUtility $transformationUtility
	 *
	 * @return string
	 */
	public function transform($configuration, $value, $transformationUtility) {
		if (MathUtility::canBeInterpretedAsInteger($value) || strcmp($configuration->getMath(),
				'')
		) {
			$value = (int)$value;
			list($operator, $operand) = GeneralUtility::trimExplode(' ',
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
