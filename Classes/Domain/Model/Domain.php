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
class Tx_NaworkUri_Domain_Model_Domain extends Tx_Extbase_DomainObject_AbstractEntity {

	/**
	 *
	 * @var string
	 */
	protected $domainname;

	public function getDomainname() {
		return $this->domainname;
	}

}

?>
