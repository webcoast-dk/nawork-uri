<?php

namespace Nawork\NaworkUri\Service\Transformation;

/**
 * Description of ValueMap
 *
 * @author Thorben Kapp <thorben@work.de>
 */
class ValueMapTransformationService implements \Nawork\NaworkUri\Service\TransformationServiceInterface {

	/**
	 * 
	 * @param \SimpleXMLElement $parameterConfiguration
	 * @param mixed $value
	 * @param \Nawork\NaworkUri\Utility\TransformationUtility $transformationUtility
	 * @return string
	 */
	public function transform($parameterConfiguration, $value, $transformationUtility) {
		if ($parameterConfiguration->map instanceof \SimpleXMLElement) {
			$transformedValue = NULL;
			/* @var $mapping \SimpleXMLElement */
			foreach ($parameterConfiguration->map->children() as $mapping) {
				if ((string) $mapping->value === (string) $value) {
					/* @var $mappingDetails \SimpleXMLElement */
					foreach ($mapping->children() as $mappingDetails) {
						if ($mappingDetails->getName() == "replacement") {
							if ($transformedValue === NULL && !$mappingDetails->attributes()->language) {
								$transformedValue = (string) $mappingDetails;
							}
							if ($mappingDetails->attributes()->language && intval($mappingDetails->attributes()->language) === intval($transformationUtility->getLanguage())) {
								$transformedValue = (string) $mappingDetails;
							}
						}
					}
				}
			}
			if($transformedValue !== NULL) {
				$value = $transformedValue;
			}
		}
		return $value;
	}

}

?>