<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Url
 *
 * @author thorben
 */
class Tx_NaworkUri_Domain_Model_Filter extends Tx_Extbase_DomainObject_AbstractEntity {

	/**
	 *
	 * @var int
	 */
	protected $pageId;

	/**
	 *
	 * @var Tx_NaworkUri_Domain_Model_Domain
	 */
	protected $domain;

	/**
	 *
	 * @var Tx_NaworkUri_Domain_Model_Language
	 */
	protected $language;

	/**
	 *
	 * @var string
	 */
	protected $path;

	/**
	 *
	 * @var array
	 */
	protected $types = array();

	/**
	 *
	 * @var string
	 */
	protected $scope;

	/**
	 *
	 * @var int
	 */
	protected $offset = 0;

	/**
	 *
	 * @var int
	 */
	protected $limit = 0;

	public function getPageId() {
		return $this->pageId;
	}

	public function getDomain() {
		return $this->domain;
	}

	public function getLanguage() {
		return $this->language;
	}

	public function getPath() {
		return $this->path;
	}

	public function getTypes() {
		return $this->types;
	}

	public function getScope() {
		return $this->scope;
	}

	public function getOffset() {
		return $this->offset;
	}

	public function getLimit() {
		return $this->limit;
	}

	public function setPageId($pageId) {
		$this->pageId = $pageId;
	}

	public function setTypes($types) {
		$this->types = $types;
	}

	public function setDomain($domain) {
		$this->domain = $domain;
	}

	public function setLanguage($language) {
		$this->language = $language;
	}

	public function setPath($path) {
		$this->path = $path;
	}

	public function setScope($scope) {
		$this->scope = $scope;
	}

	public function setOffset($offset) {
		$this->offset = $offset;
	}

	public function setLimit($limit) {
		$this->limit = $limit;
	}

	public function addType($type) {
		if (!in_array($type, $this->types)) {
			$this->types[] = $type;
		}
	}

}

?>
