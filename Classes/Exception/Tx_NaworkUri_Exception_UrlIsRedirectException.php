<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Tx_NaworkUri_Exception_UrlIsRedirectException
 *
 * @author thorben
 */
class Tx_NaworkUri_Exception_UrlIsRedirectException extends Exception {

	protected $url;

	public function __construct($url) {
		parent::__construct('The url is a redirect');
		$this->url = $url;
	}

	public function getUrl() {
		return $this->url;
	}

}

?>
