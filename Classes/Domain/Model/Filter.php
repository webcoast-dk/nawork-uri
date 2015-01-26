<?php

namespace Nawork\NaworkUri\Domain\Model;

/**
 * Description of Url
 *
 * @author thorben
 */
class Filter extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 *
	 * @var int
	 */
	protected $pageId;

	/**
	 *
	 * @var \Nawork\NaworkUri\Domain\Model\Domain[]|array
	 */
	protected $domains;

	/**
	 *
	 * @var \Nawork\NaworkUri\Domain\Model\Language
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

	public function getDomains() {
		return $this->domains;
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

	public function setDomains($domains) {
		$this->domains = $domains;
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
