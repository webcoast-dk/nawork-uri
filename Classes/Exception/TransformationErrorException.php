<?php

namespace Nawork\NaworkUri\Exception;

/**
 * Description of TransformationErrorException
 *
 * @author Thorben Kapp <thorben@work.de>
 */
class TransformationErrorException extends \Exception {

	protected $message = 'The transformation could not be completed, the reason was: ';

	public function __construct($reason) {
		$this->message .= $reason;
	}

}
