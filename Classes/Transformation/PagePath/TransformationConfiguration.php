<?php

namespace Nawork\NaworkUri\Transformation\PagePath;


class TransformationConfiguration extends \Nawork\NaworkUri\Transformation\AbstractTransformationConfiguration {
	protected $type = 'PagePath';

	protected $additionalProperties = array(
		'Table' => 'string',
		'TranslationTable' => 'string',
		'Fields' => 'string',
		'PathOverrideField' => 'string',
		'PathSeparator' => 'string',
		'ExcludeFromPathField' => 'string'
	);

	/**
	 * @var string
	 */
	protected $table = 'pages';
	protected $translationTable = 'pages_language_overlay';
	protected $fields = 'nav_title//title';
	protected $pathOverrideField = 'tx_urlrewrite_pathoverride';
	protected $pathSeparator = '/';
	protected $excludeFromPathField = 'tx_urlrewrite_exclude';

	public function getTable() {
		return $this->table;
	}

	/**
	 * @return string
	 */
	public function getTranslationTable() {
		return $this->translationTable;
	}

	public function getFields() {
		return $this->fields;
	}

	public function getPathOverrideField() {
		return $this->pathOverrideField;
	}

	public function getPathSeparator() {
		return $this->pathSeparator;
	}

	public function getExcludeFromPathField() {
		return $this->excludeFromPathField;
	}

	public function setTable($table) {
		$this->table = $table;
	}

	/**
	 * @param string $translationTable
	 */
	public function setTranslationTable($translationTable) {
		$this->translationTable = $translationTable;
	}

	public function setFields($fields) {
		$this->fields = $fields;
	}

	public function setPathOverrideField($pathOverrideField) {
		$this->pathOverrideField = $pathOverrideField;
	}

	public function setPathSeparator($pathSeparator) {
		$this->pathSeparator = $pathSeparator;
	}

	public function setExcludeFromPathField($excludeFromPathField) {
		$this->excludeFromPathField = $excludeFromPathField;
	}
}
 