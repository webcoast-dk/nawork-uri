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
class Tx_NaworkUri_Domain_Model_Url extends Tx_Extbase_DomainObject_AbstractEntity {

	/**
	 *
	 * @var Tx_NaworkUri_Domain_Model_Language
	 */
	protected $language;

	/**
	 *
	 * @var Tx_NaworkUri_Domain_Model_Domain
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
	 * 2: redirect
	 *
	 * @var int
	 */
	protected $type;

	public function getLanguage() {
		return $this->language;
	}

	public function getDomain() {
		return $this->domain;
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

	public function setLocked($locked) {
		$this->locked = $locked;
	}

}

?>
