<?php

namespace Nawork\NaworkUri\Transformation\Database;


class ConfigurationReader extends \Nawork\NaworkUri\Transformation\AbstractConfigurationReader {
	/**
	 * Add additional configuration, read from the given xml to the transformation configuration object.
	 * The configuration object should must be treated as reference, so nothing is returned here.
	 *
	 * @param \SimpleXMLElement                                                   $xml
	 * @param \Nawork\NaworkUri\Transformation\Database\TransformationConfiguration $transformationConfiguration
	 */
	public function buildConfiguration($xml, &$transformationConfiguration) {
		if($xml->Table && strcmp((string)$xml->Table, '')) {
			$transformationConfiguration->setTable((string)$xml->Table);
		}

		if($xml->CompareField && strcmp((string)$xml->CompareField, '')) {
			$transformationConfiguration->setCompareField((string)$xml->CompareField);
		}

		if($xml->Patterns && $xml->Patterns->children()->count() > 0) {
			/* @var $pattern \SimpleXMLElement */
			foreach($xml->Patterns->children() as $pattern) {
				if(!$pattern->attributes()->Language || !strcmp((string)$pattern->attributes()->Language, '')) {
					$transformationConfiguration->addPattern('default', (string)$pattern);
				} else {
					$transformationConfiguration->addPattern((int)$pattern->attributes()->Language, (string)$pattern);
				}
			}
		}
	}
}
 