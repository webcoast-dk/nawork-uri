<?php

namespace Nawork\NaworkUri\Configuration;


class ParametersConfiguration {
	/**
	 * @var array|\Nawork\NaworkUri\Transformation\AbstractTransformationConfiguration[]
	 */
	protected $parameterTransformationConfigurations = array();

	/**
	 * @param \Nawork\NaworkUri\Transformation\AbstractTransformationConfiguration $transformationConfiguration
	 */
	public function addTransformationConfiguration($transformationConfiguration) {
		$this->parameterTransformationConfigurations[$transformationConfiguration->getName()] = $transformationConfiguration;
	}

	/**
	 * @return array|\Nawork\NaworkUri\Transformation\AbstractTransformationConfiguration[]
	 */
	public function getParameterTransformationConfigurations() {
		return $this->parameterTransformationConfigurations;
	}

	public function getParameterTransformationConfigurationByName($name) {
		if (!array_key_exists($name, $this->parameterTransformationConfigurations)) {
			throw new \Exception('There is no transformation configuration for parameter "' . $name . '"', 1395857024);
		}
		return $this->parameterTransformationConfigurations[$name];
	}
}
 