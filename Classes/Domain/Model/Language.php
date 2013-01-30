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
class Tx_NaworkUri_Domain_Model_Language extends Tx_Extbase_DomainObject_AbstractEntity {

	/**
	 *
	 * @var string
	 */
	protected $title;
	/**
	 *
	 * @var string
	 */
	protected $flag;

	public function getTitle() {
		return $this->title;
	}

	public function getFlag() {
		if(file_exists(PATH_site.'typo3/gfx/flags/'.$this->flag.'.gif')) {
			return $this->flag;
		}
		return NULL;
	}

}

?>
