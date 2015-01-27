<?php

namespace Nawork\NaworkUri\Exception;


class InheritanceException extends \Exception{
	public function __construct($type, $domain) {
		$this->message = 'Extending configuration "'.$type.'" from domain "'.$domain.'" caused an inheritance loop';
	}
}
 