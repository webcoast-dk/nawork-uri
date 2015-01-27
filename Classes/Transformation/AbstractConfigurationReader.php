<?php

namespace Nawork\NaworkUri\Transformation;


abstract class AbstractConfigurationReader implements \TYPO3\CMS\Core\SingletonInterface{
	/**
	 * Add additional configuration, read from the given xml to the transformation configuration object.
	 * The configuration object should must be treated as reference, so nothing is returned here.
	 *
	 * @param \SimpleXMLElement                                                   $xml
	 * @param \Nawork\NaworkUri\Transformation\AbstractTransformationConfiguration $transformationConfiguration
	 */
	abstract public function buildConfiguration($xml, &$transformationConfiguration);
}
 