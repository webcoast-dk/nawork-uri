<?php

namespace Nawork\NaworkUri\Exception;

/**
 * Description of DbErrorException
 *
 * @author thorben
 */
class DbErrorException extends \Exception {
	private $sqlError;

	function __construct($sqlError) {
		$this->sqlError = $sqlError;
		$this->message = $sqlError;
	}

	public function getSqlError() {
		return $this->sqlError;
	}
}
