<?php

namespace Nawork\NaworkUri\Transformation\PagePath;


use Nawork\NaworkUri\Transformation\AbstractConfigurationReader;

class ConfigurationReader extends AbstractConfigurationReader {

	/**
	 * Add additional configuration, read from the given xml to the transformation configuration object.
	 * The configuration object should must be treated as reference, so nothing is returned here.
	 *
	 * @param \SimpleXMLElement                                                    $xml
	 * @param \Nawork\NaworkUri\Transformation\PagePath\TransformationConfiguration $transformationConfiguration
	 */
	public function buildConfiguration($xml, &$transformationConfiguration) {
		if ($xml->Table && strcmp((string)$xml->Table, '')) {
			$transformationConfiguration->setTable((string)$xml->Table);
		}

		if ($xml->TranslationTable && strcmp((string)$xml->TranslationTable, '')) {
			$transformationConfiguration->setTranslationTable((string)$xml->TranslationTable);
		}

		if ($xml->Fields && strcmp((string)$xml->Fields, '')) {
			$transformationConfiguration->setFields((string)$xml->Fields);
		}

		if ($xml->PathOverrideField && strcmp((string)$xml->PathOverrideField, '')) {
			$transformationConfiguration->setPathOverrideField((string)$xml->PathOverrideField);
		}

		if ($xml->PathSeparator && strcmp((string)$xml->PathSeparator, '')) {
			$transformationConfiguration->setPathSeparator((string)$xml->PathSeparator);
		}

		if ($xml->ExcludeFromPathField && strcmp((string)$xml->ExcludeFromPathField, '')) {
			$transformationConfiguration->setExcludeFromPathField((string)$xml->ExcludeFromPathField);
		}
	}
}
