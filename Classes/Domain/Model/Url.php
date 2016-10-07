<?php

namespace Nawork\NaworkUri\Domain\Model;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Description of Url
 *
 * @author thorben
 */
class Url extends AbstractEntity {

	/**
	 *
	 * @var \Nawork\NaworkUri\Domain\Model\Language
	 */
	protected $language;

	/**
	 *
	 * @var \Nawork\NaworkUri\Domain\Model\Domain
	 */
	protected $domain;

	/**
	 *
	 * @var string
	 */
	protected $path;

	/**
	 *
	 * @var string
	 */
	protected $parameters;

	/**
	 *
	 * @var boolean
	 */
	protected $locked;

	/**
	 *
	 * @var int
	 */
	protected $pageUid;

	/**
	 * URL type
	 * 0: normal
	 * 1: old
	 * 2: redirect to url
	 * 3: redirect to page
	 *
	 * @var int
	 */
	protected $type;

	/**
	 *
	 * @var string
	 */
	protected $redirectPath;

	public function getLanguage() {
		return $this->language;
	}

	public function getDomain() {
		return $this->domain;
	}

	/**
	 * @return int
	 */
	public function getPageUid() {
		return $this->pageUid;
	}

	public function getPath() {
		return $this->path;
	}

	public function getParameters() {
		return $this->parameters;
	}

	public function getLocked() {
		return $this->locked;
	}

	public function getType() {
		return $this->type;
	}

	public function getRedirectPath() {
		return $this->redirectPath;
	}

	public function setLocked($locked) {
		$this->locked = $locked;
	}

}
