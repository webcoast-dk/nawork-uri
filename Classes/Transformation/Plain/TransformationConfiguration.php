<?php

namespace Nawork\NaworkUri\Transformation\Plain;


class TransformationConfiguration extends \Nawork\NaworkUri\Transformation\AbstractTransformationConfiguration{
	protected $type = 'Plain';
	/**
	 * @var string
	 */
	protected $math;
	/**
	 * @var array
	 */
	protected $wraps;

	public function __construct() {
		$this->wraps = array();
	}

	/**
	 * @param int $language
	 *
	 * @return bool|string
	 */
	public function getWrap($language) {
		if (array_key_exists($language, $this->wraps)) {
			return $this->wraps[$language];
		} elseif (array_key_exists('default', $this->wraps)) {
			return $this->wraps['default'];
		}
		return FALSE;
	}

	/**
	 * @return string
	 */
	public function getMath() {
		return $this->math;
	}

	/**
	 * @param int|string $language
	 * @param string     $wrap
	 */
	public function addWrap($language, $wrap) {
		$this->wraps[$language] = $wrap;
	}

	/**
	 * @param string $math
	 */
	public function setMath($math) {
		$this->math = $math;
	}
}
 