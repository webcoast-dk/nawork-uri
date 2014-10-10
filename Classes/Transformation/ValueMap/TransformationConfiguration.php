<?php

namespace Nawork\NaworkUri\Transformation\ValueMap;


class TransformationConfiguration extends \Nawork\NaworkUri\Transformation\AbstractTransformationConfiguration {
	protected $type = 'ValueMap';

	/**
	 *
	 * @var array
	 */
	protected $mappings = array();

	public function addMapping($language, $value, $replacement) {
		if (!array_key_exists($value, $this->mappings)) {
			$this->mappings[$value] = array();
		}
		$this->mappings[$value][$language] = $replacement;
	}

	/**
	 * @param $language
	 * @param $value
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function getMapping($language, $value) {
		if (array_key_exists($value, $this->mappings)) {
			if (array_key_exists($language, $this->mappings[$value])) {
				return $this->mappings[$value][$language];
			} elseif (array_key_exists('default', $this->mappings[$value])) {
				return $this->mappings[$value]['default'];
			}
		}
		throw new \Exception('No mapping found for value "'.$value.'"', 1394822329);
	}
}
 