<?php

namespace Nawork\NaworkUri\Transformation;


class AbstractTransformationConfiguration {
	/**
	 * @var string
	 */
	protected $name;
	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @param string $name
	 */
	public function setName($name) {
		$this->name = $name;
	}
}
 