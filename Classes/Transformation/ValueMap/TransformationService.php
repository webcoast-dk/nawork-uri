<?php

namespace Nawork\NaworkUri\Transformation\ValueMap;
use Nawork\NaworkUri\Exception\TransformationException;
use Nawork\NaworkUri\Transformation\AbstractTransformationService;

/**
 * Description of ValueMap
 *
 * @author Thorben Kapp <thorben@work.de>
 */
class TransformationService extends AbstractTransformationService {

	/**
	 * @param \Nawork\NaworkUri\Transformation\ValueMap\TransformationConfiguration $configuration
	 * @param string|int                                                           $value
	 * @param \Nawork\NaworkUri\Utility\TransformationUtility                       $transformationUtility
	 *
	 * @return string
	 *
	 * @throws \Nawork\NaworkUri\Exception\TransformationException
	 */
	public function transform($configuration, $value, $transformationUtility) {
		try {
			$value = $configuration->getMapping($transformationUtility->getLanguage(), $value);
		} catch (\Exception $e) {
			throw new TransformationException($configuration->getName(), $configuration->getType(), $value, $e);
		}
		return $value;
	}

}
