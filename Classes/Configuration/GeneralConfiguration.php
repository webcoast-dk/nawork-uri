<?php

namespace Nawork\NaworkUri\Configuration;


class GeneralConfiguration {
	/**
	 * @var bool
	 */
	protected $disabled = FALSE;
	/**
	 * @var string
	 */
	protected $append = '.html';

	/**
	 * @var string
	 */
	protected $pathSeparator = '/';

	/**
	 * @var int
	 */
	protected $storagePage = 0;

	/**
	 * @return string
	 */
	public function getAppend() {
		return $this->append;
	}

	/**
	 * @return boolean
	 */
	public function getDisabled() {
		return $this->disabled;
	}

	/**
	 * @return string
	 */
	public function getPathSeparator() {
		return $this->pathSeparator;
	}

	/**
	 * @return int
	 */
	public function getStoragePage() {
		return $this->storagePage;
	}

	/**
	 * @param string $append
	 */
	public function setAppend($append) {
		$this->append = $append;
	}

	/**
	 * @param boolean $disabled
	 */
	public function setDisabled($disabled) {
		$this->disabled = $disabled;
	}

	/**
	 * @param string $pathSeparator
	 */
	public function setPathSeparator($pathSeparator) {
		$this->pathSeparator = $pathSeparator;
	}

	/**
	 * @param int $storagePage
	 */
	public function setStoragePage($storagePage) {
		$this->storagePage = $storagePage;
	}
}
 