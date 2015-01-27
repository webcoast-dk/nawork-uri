<?php

namespace Nawork\NaworkUri\Transformation\Database;


class TransformationConfiguration extends \Nawork\NaworkUri\Transformation\AbstractTransformationConfiguration {
	protected $type = 'Database';

	protected $additionalProperties = array(
		'Table' => 'string',
		'CompareField' => 'string'
	);

	/**
	 * @var string
	 */
	protected $table;
	/**
	 * @var string
	 */
	protected $compareField = 'uid';
	/**
	 * @var string
	 */
	protected $patterns = array();

	/**
	 * @return string
	 */
	public function getCompareField() {
		return $this->compareField;
	}

	/**
	 * @return string
	 */
	public function getTable() {
		return $this->table;
	}

	/**
	 * @param string $compareField
	 */
	public function setCompareField($compareField) {
		$this->compareField = $compareField;
	}

	/**
	 * @param string $table
	 */
	public function setTable($table) {
		$this->table = $table;
	}

	public function addPattern($language, $pattern) {
		$this->patterns[$language] = $pattern;
	}

	public function getPattern($language) {
		if(array_key_exists($language, $this->patterns)) {
			return $this->patterns[$language];
		} elseif(array_key_exists('default', $this->patterns)) {
			return $this->patterns['default'];
		} else {
			return "";
		}
	}
}
 