<?php

namespace Nawork\NaworkUri\Transformation\Hidden;


class ConfigurationReader extends \Nawork\NaworkUri\Transformation\AbstractConfigurationReader {
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
 