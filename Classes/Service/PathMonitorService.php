<?php

class Tx_Naworkuri_Service_PathMonitorService {

	protected $domain;
	protected $cUrl;

	/**
	 * Constructor
	 *
	 * @param string $domain
	 * @param null|string $httpUser
	 * @param null|string $httpPassword
	 * @param bool $sslNoVerifyPeer
	 */
	public function __construct ($domain, $httpUser = NULL, $httpPassword = NULL, $sslNoVerifyPeer = FALSE) {
		$this->domain = $domain;

		$this->cUrl = curl_init();
		curl_setopt_array(
			$this->cUrl,
			array(
				CURLOPT_HTTPGET => TRUE,
				CURLOPT_RETURNTRANSFER => TRUE,
				CURLOPT_FOLLOWLOCATION => FALSE,
				CURLOPT_MAXREDIRS => 0,
				CURLOPT_HEADER => TRUE
				//CURLOPT_NOBODY => TRUE
			)
		);

		if (!is_null($httpUser) && !is_null($httpPassword)) {
			curl_setopt($this->cUrl, CURLOPT_USERPWD, $httpUser . ':' . $httpPassword);
		}

		if ($sslNoVerifyPeer ) {
			curl_setopt($this->cUrl, CURLOPT_SSL_VERIFYPEER, FALSE);
		}
	}

	/**
	 * Destructor
	 */
	public function __destruct (){
		if ($this->cUrl) {
			curl_close($this->cUrl);
		}
	}

	/**
	 * Test a path
	 *
	 * @param $path
	 * @param $expectedStatus
	 * @param $expectedRedirect
	 * @param $https use https
	 * @return Tx_naworkuri_Domain_Model_PathTestResult
	 */
	public function testPath($path, $expectedStatus = NULL, $expectedRedirect = NULL, $https = FALSE) {

		if ($https) {
			$url = 'https://' . $this->domain . $path;
		} else {
			$url = 'http://' . $this->domain . $path;
		}

		curl_setopt($this->cUrl, CURLOPT_URL, $url);
		$response = curl_exec($this->cUrl);
		list($header, $body) = explode("\n\n", $response, 2);

		// extract response headers
		$responseHeaders = array();
		$headerLines = explode("\n", $header);
		foreach($headerLines as $headerLine) {
			list($header, $value) = explode (': ', $headerLine, 2);
			if ($header && $value) {
				$responseHeaders[$header] = trim($value);
			}
		}

		$pathTestResult = new Tx_naworkuri_Domain_Model_PathTestResult();

		if ($response === FALSE) {
			$pathTestResult->setSuccess(FALSE);
			return $pathTestResult;
		} else {
			$pathTestResult->setSuccess(TRUE);
		}

		if ($expectedStatus) {
			$httpStatusCode = curl_getinfo($this->cUrl, CURLINFO_HTTP_CODE);
			$pathTestResult->setStatus($httpStatusCode);
			if ($httpStatusCode == $expectedStatus){
				$pathTestResult->setStatusSuccess(TRUE);
				$pathTestResult->addInfo("http ok");
			} else {
				$pathTestResult->setStatusSuccess(FALSE);
				$pathTestResult->setSuccess(FALSE);
				$pathTestResult->addInfo("http fail");
			}
		}

		if ($expectedRedirect) {

			$redirect = $responseHeaders['Location'];

			// strip current domain
			if (strpos('http://' .  $this->domain, $expectedRedirect) === 0) {
				$expectedRedirect = str_replace('http://' .  $this->domain, '', $expectedRedirect);
			}

			if (strpos('http://' .  $this->domain, $redirect) === 0){
				$redirect = str_replace('http://' .  $this->domain, '', $redirect);
			}

			$pathTestResult->setRedirect($redirect);

			if ($redirect && $expectedRedirect == $expectedRedirect) {
				$pathTestResult->setRedirectSuccess(TRUE);
				$pathTestResult->addInfo("redirect ok");
			} else {
				$pathTestResult->setSuccess(FALSE);
				$pathTestResult->setRedirectSuccess(FALSE);
				$pathTestResult->addInfo("redirect fail");

			}
		}

		return $pathTestResult;
	}

}
