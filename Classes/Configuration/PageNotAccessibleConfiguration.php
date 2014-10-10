<?php

namespace Nawork\NaworkUri\Configuration;


class PageNotAccessibleConfiguration {
	const BEHAVIOR_MESSAGE = 0;
	const BEHAVIOR_PAGE = 1;
	const BEHAVIOR_REDIRECT = 2;

	/**
	 * An integer constant that defines what to to if a page (path) was not found
	 *
	 * @var int
	 */
	protected $behavior = self::BEHAVIOR_MESSAGE;

	/**
	 * The http status code to send. Should be one of the
	 * \TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_* constants
	 *
	 * @var string
	 */
	protected $status = \TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_404;

	/**
	 * What to do on page not found: Depends on the behavior. This can be a
	 * message hat is displayed or a url that is loaded or redirected to.
	 *
	 * @var string
	 */
	protected $value = '<h1>The page you requested was not found!</h1>';

	public function getBehavior() {
		return $this->behavior;
	}

	public function getStatus() {
		return $this->status;
	}

	public function getValue() {
		return $this->value;
	}

	public function setBehavior($behavior) {
		$this->behavior = $behavior;
	}

	public function setStatus($status) {
		$this->status = $status;
	}

	public function setValue($content) {
		$this->value = $content;
	}
}
 