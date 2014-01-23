<?php

namespace Nawork\NaworkUri\Service;

/**
 * Description of TransformationServiceInterface
 *
 * @author Thorben Kapp <thorben@work.de>
 */
interface TransformationServiceInterface {

	/**
	 * @param \SimpleXMLElement $parameterConfiguration
	 * @param mixed $value
	 * @param \Nawork\NaworkUri\Utility\TransformationUtility $transformationUtility
	 * @return string The converted value
	 */
	public function transform($parameterConfiguration, $value, $transformationUtility);
}

?>