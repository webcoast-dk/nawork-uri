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
		$compareField = (string) $parameterConfiguration->compareField;
		if (empty($compareField))
			$compareField = 'uid';

		$table = (string) $parameterConfiguration->table;
		$replacement = (string) $parameterConfiguration->replacement;
//		if(preg_match_all('/[{.*?}}/', $replacement))
		
		$selectFields = (string) $parameterConfiguration->selectFields;
		if(empty($selectFields)) {
			
		}
	}

}

?>