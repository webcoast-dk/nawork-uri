<?php

namespace Nawork\NaworkUri\Transformation\Plain;


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
		if($xml->Math && strcmp((string)$xml->Math, '')) {
			$transformationConfiguration->setMath((string)$xml->Math);
		}

		if($xml->Wraps && $xml->Wraps->children()->count() > 0) {
			/* @var $wrap \SimpleXMLElement */
			foreach($xml->Wraps->children() as $wrap) {
				if(!$wrap->attributes()->Language || !strcmp((string)$wrap->attributes()->Language, '')) {
					$transformationConfiguration->addWrap('default', (string)$wrap);
				} else {
					$transformationConfiguration->addWrap((int)$wrap->attributes()->Language, (string)$wrap);
				}
			}
		}
	}
}
