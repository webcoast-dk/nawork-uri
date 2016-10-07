<?php

namespace Nawork\NaworkUri\Transformation;


use TYPO3\CMS\Core\SingletonInterface;

abstract class AbstractConfigurationReader implements SingletonInterface {
	/**
	 * Add additional configuration, read from the given xml to the transformation configuration object.
	 * The configuration object should must be treated as reference, so nothing is returned here.
	 *
	 * @param \SimpleXMLElement                                                   $xml
	 * @param \Nawork\NaworkUri\Transformation\AbstractTransformationConfiguration $transformationConfiguration
	 */
	abstract public function buildConfiguration($xml, &$transformationConfiguration);
}
