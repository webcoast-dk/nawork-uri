<?php

namespace Nawork\NaworkUri\Exception;


class TransformationException extends \Exception {
	/**
	 * @var string
	 */
	protected $parameter;
	/**
	 * @var string
	 */
	protected $transformationType;
	/**
	 * @var int|string
	 */
	protected $value;
	/**
	 * @var \Exception|null
	 */
	protected $exception;

	/**
	 *
	 * @param string $parameter Parameter name
	 * @param string $transformationType
	 * @param string|int $value The parameter's value
	 * @param \Exception $exception The exception that was thrown before
	 */
	public function __construct($parameter, $transformationType, $value, $exception = NULL) {
		$this->parameter = $parameter;
		$this->transformationType = $transformationType;
		$this->value = $value;
		$this->exception = $exception;
		$this->message = '';
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

	/**
	 * @return int|string
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * @return \Exception|null
	 */
	public function getException() {
		return $this->exception;
	}
}
 