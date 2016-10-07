<?php

namespace Nawork\NaworkUri\Transformation\Hidden;


use Nawork\NaworkUri\Transformation\AbstractConfigurationReader;

class ConfigurationReader extends AbstractConfigurationReader {
	/**
	 * Add additional configuration, read from the given xml to the transformation configuration object.
	 * The configuration object should must be treated as reference, so nothing is returned here.
	 *
	 * @param \SimpleXMLElement                                                    $xml
	 * @param \Nawork\NaworkUri\Transformation\Plain\TransformationConfiguration $transformationConfiguration
	 */
	public function buildConfiguration($xml, &$transformationConfiguration) {

	}
}
