<?php

namespace Nawork\NaworkUri\Domain\Model;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Description of Url
 *
 * @author thorben
 */
class Filter extends AbstractEntity implements \JsonSerializable {

	/**
	 *
	 * @var int
	 */
	protected $pageId;

	/**
	 *
	 * @var \Nawork\NaworkUri\Domain\Model\Domain
	 */
	protected $domain;

	/**
	 *
	 * @var \Nawork\NaworkUri\Domain\Model\Language
	 */
	protected $language;

	/**
	 *
	 * @var string
	 */
	protected $path = '';

	/**
	 *
	 * @var array
	 */
	protected $types = array();

	/**
	 *
	 * @var string
	 */
	protected $scope = '';

	/**
	 *
	 * @var int
	 */
	protected $offset = 0;

    /**
     * @var string
     */
    protected $parameters = '';

    /**
     * @var bool
     */
    protected $ignoreLanguage = false;

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

    /**
     * @return string
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @return boolean
     */
    public function getIgnoreLanguage()
    {
        return $this->ignoreLanguage;
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

	public function addType($type) {
		if (!in_array($type, $this->types)) {
			$this->types[] = $type;
		}
	}

    /**
     * @param string $parameters
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @param boolean $ignoreLanguage
     */
    public function setIgnoreLanguage($ignoreLanguage)
    {
        $this->ignoreLanguage = $ignoreLanguage;
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *        which is a value of any type other than a resource.
     * @since 5.4.0
     */
    function jsonSerialize()
    {
        return [
            'pageId' => $this->pageId,
            'domain' => $this->domain->getUid(),
            'language' => $this->language instanceof Language ? $this->language->getUid() : null,
            'ignoreLanguage' => $this->ignoreLanguage,
            'types' => $this->types,
            'scope' => $this->scope
        ];
    }
}
