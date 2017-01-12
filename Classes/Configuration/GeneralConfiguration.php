<?php

namespace Nawork\NaworkUri\Configuration;


use TYPO3\CMS\Core\Utility\HttpUtility;

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
    protected $appendIfNotPattern = NULL;

	/**
	 * @var string
	 */
	protected $pathSeparator = '/';

	/**
	 * @var string
	 */
	protected $redirectStatus = HttpUtility::HTTP_STATUS_301;

	/**
	 * @return string
	 */
	public function getAppend() {
		return $this->append;
	}

    /**
     * @return string
     */
    public function getAppendIfNotPattern()
    {
        return $this->appendIfNotPattern;
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
	 * @return string
	 */
	public function getRedirectStatus() {
		return $this->redirectStatus;
	}

	/**
	 * @param string $append
	 */
	public function setAppend($append) {
		$this->append = $append;
	}

    /**
     * @param string $appendIfNotPattern
     */
    public function setAppendIfNotPattern($appendIfNotPattern)
    {
        $this->appendIfNotPattern = $appendIfNotPattern;
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
	 * @param string $redirectStatus
	 */
	public function setRedirectStatus($redirectStatus) {
		$this->redirectStatus = $redirectStatus;
	}
}
 