<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of DbErTx_NaworkUri_Exception_DbErrorExceptionorException
 *
 * @author thorben
 */
class Tx_NaworkUri_Exception_DbErrorException extends Exception {
	private $sqlError;

	function __construct($sqlError) {
		$this->sqlError = $sqlError;
	}

	public function getSqlError() {
		return $this->sqlError;
	}



}

?>
