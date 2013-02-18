<?php

class Tx_naworkuri_Domain_Model_PathTestResult {

	/**
	 * @var bool
	 */
	protected $success = FALSE;

	/**
	 * @var int|null
	 */
	protected $status;

	/**
	 * @var bool
	 */
	protected $statusSuccess = TRUE;

	/**
	 * @var null|string
	 */
	protected $redirect;

	/**
	 * @var bool
	 */
	protected $redirectSuccess = TRUE;

	/**
	 * @var string
	 */
	protected $info;

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
	 * @param boolean $success
	 */
	public function setSuccess($success) {
		$this->success = $success;
	}

	/**
	 * @return boolean
	 */
	public function getSuccess() {
		return $this->success;
	}

	/**
	 * @param boolean $redirectSuccess
	 */
	public function setRedirectSuccess($redirectSuccess) {
		$this->redirectSuccess = $redirectSuccess;
	}

	/**
	 * @return boolean
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
	 * @return boolean
	 */
	public function getStatusSuccess() {
		return $this->statusSuccess;
	}

	/**
	 * @param string $info
	 */
	public function addInfo($info) {
		if ($this->info) $this->info .= chr(10);
		$this->info .= $info;
	}

	/**
	 * @return string
	 */
	public function getInfo(){
		return $this->info;
	}

}
