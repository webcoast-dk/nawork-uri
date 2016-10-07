<?php

namespace Nawork\NaworkUri\Transformation\ValueMap;


use Nawork\NaworkUri\Transformation\AbstractConfigurationReader;

class ConfigurationReader extends AbstractConfigurationReader {
	/**
	 * Add additional configuration, read from the given xml to the transformation configuration object.
	 * The configuration object should must be treated as reference, so nothing is returned here.
	 *
	 * @param \SimpleXMLElement $xml
	 * @param \Nawork\NaworkUri\Transformation\ValueMap\TransformationConfiguration $transformationConfiguration
	 */
	public function buildConfiguration($xml, &$transformationConfiguration) {
		if ($xml->Mappings && $xml->Mappings->children()->count() > 0) {
			/* @var $mapping \SimpleXMLElement */
			foreach ($xml->Mappings->children() as $mapping) {
				if ($mapping->getName() == 'Mapping') {
					if ($mapping->attributes()->Value && strcmp((string)$mapping->attributes()->Value, '') &&
						$mapping->children()->count() > 0
					) {
						$value = (string)$mapping->attributes()->Value;
						/* @var $replacement \SimpleXMLElement */
						foreach ($mapping->children() as $replacement) {
							if ($replacement->getName() == 'Replacement') {
								if (!$replacement->attributes()->Language ||
									!strcmp((string)$replacement->attributes()->Language, '')
								) {
									$transformationConfiguration->addMapping('default', $value, (string)$replacement);
								} else {
									$transformationConfiguration->addMapping((string)$replacement->attributes()->Language,
										$value,
										(string)$replacement);
								}
							}
						}
					}
				}
			}
		}
	}
}
