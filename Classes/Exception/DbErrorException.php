<?php

namespace Nawork\NaworkUri\Exception;

/**
 * Description of DbErTx_NaworkUri_Exception_DbErrorExceptionorException
 *
 * @author thorben
 */
class DbErrorException extends \Exception {
	private $sqlError;

	function __construct($sqlError) {
		$this->sqlError = $sqlError;
	}

	public function getSqlError() {
		return $this->sqlError;
	}



}

?>
