<?php

namespace Nawork\NaworkUri\Transformation;

/**
 * Description of TransformationServiceInterface
 *
 * @author Thorben Kapp <thorben@work.de>
 */
abstract class AbstractTransformationService implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @param \Nawork\NaworkUri\Transformation\AbstractTransformationConfiguration $configuration
	 * @param mixed $value
	 * @param \Nawork\NaworkUri\Utility\TransformationUtility $transformationUtility
	 * @return string The converted value
	 */
	public abstract function transform($configuration, $value, $transformationUtility);
}
