<?php

namespace Nawork\NaworkUri\Exception;


class TransformationServiceException extends \Exception {
	/**
	 * @var string
	 */
	protected $parameter;
	/**
	 * @var string
	 */
	protected $transformationType;
	/**
	 * @var string
	 */
	protected $message;

	/**
	 * @var \Exception|NULL
	 */
	protected $exception;

	/**
	 *
	 * @param string          $parameter Parameter name
	 * @param string          $transformationType
	 * @param string          $message   The message
	 * @param \Exception|NULL $exception
	 */
	public function __construct($parameter, $transformationType, $message, $exception = NULL) {
		$this->parameter = $parameter;
		$this->transformationType = $transformationType;
		$this->message = $message;
		$this->exception = $exception;
	}

	/**
	 * @return string
	 */
	public function getParameter() {
		return $this->parameter;
	}

	/**
	 * @return string
	 */
	public function getTransformationType() {
		return $this->transformationType;
	}
}
 