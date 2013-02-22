<?php

class Tx_naworkuri_Domain_Model_PathTestResult {

	/**
	 * @var string
	 */
	protected $url;

	/**
	 * @var int|null
	 */
	protected $status;

	/**
	 * @var null|string
	 */
	protected $redirect;

	/**
	 * @var null|bool
	 */
	protected $success = NULL;

	/**
	 * @var null|bool
	 */
	protected $statusSuccess = NULL;

	/**
	 * @var null|bool
	 */
	protected $redirectSuccess = NULL;

	/**
	 * @var null|bool
	 */
	protected $httpsSuccess = NULL;

	/**
	 * @var string
	 */
	protected $info = '';

	/**
	 * @param string $url
	 */
	public function setUrl($url) {
		$this->url = $url;
	}

	/**
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}


	/**
	 * @param null|string $redirect
	 */
	public function setRedirect($redirect) {
		$this->redirect = $redirect;
	}

	/**
	 * @return null|string
	 */
	public function getRedirect() {
		return $this->redirect;
	}

	/**
	 * @param int|null $status
	 */
	public function setStatus($status) {
		$this->status = $status;
	}

	/**
	 * @return int|null
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @param boolean $redirectSuccess
	 */
	public function setSuccess($success) {
		$this->success = $success;
	}

	/**
	 * @return boolean
	 */
	public function getSuccess() {
		$success = $this->success;
		if ($this->statusSuccess === FALSE) {
			$success = FALSE;
		}
		if ($this->redirectSuccess === FALSE) {
			$success = FALSE;
		}
		if ($this->httpsSuccess === FALSE) {
			$success = FALSE;
		}
		return $success;
	}

	/**
	 * @param boolean $redirectSuccess
	 */
	public function setRedirectSuccess($redirectSuccess) {
		$this->redirectSuccess = $redirectSuccess;
	}

	/**
	 * @return null|boolean
	 */
	public function getRedirectSuccess() {
		return $this->redirectSuccess;
	}

	/**
	 * @param boolean $statusSuccess
	 */
	public function setStatusSuccess($statusSuccess) {
		$this->statusSuccess = $statusSuccess;
	}

	/**
	 * @return null|boolean
	 */
	public function getStatusSuccess() {
		return $this->statusSuccess;
	}

	/**
	 * @param boolean $httpsSuccess
	 */
	public function setHttpsSuccess($httpsSuccess) {
		$this->httpsSuccess = $httpsSuccess;
	}

	/**
	 * @return null|boolean
	 */
	public function getHttpsSuccess() {
		return $this->httpsSuccess;
	}

	/**
	 * @param string $info
	 */
	public function addInfo($info) {
		$this->info .= chr(10) . $info;
	}

	/**
	 * @return string
	 */
	public function getInfo(){
		return $this->info;
	}

}
