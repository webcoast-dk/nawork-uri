<?php

namespace Nawork\NaworkUri\Transformation\ValueMap;

/**
 * Description of ValueMap
 *
 * @author Thorben Kapp <thorben@work.de>
 */
class TransformationService extends \Nawork\NaworkUri\Transformation\AbstractTransformationService {

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
			throw new \Nawork\NaworkUri\Exception\TransformationException($configuration->getName(), $configuration->getType(), $value, $e);
		}
		return $value;
	}

}

?>