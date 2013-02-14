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
	 * @return Tx_Extbase_Error_Result
	 */
	public function testPath($path, $expectedStatus = NULL, $expectedRedirect = NULL, $https = FALSE) {

		if ($https) {
			$url = 'https://' . $this->domain . $path;
		} else {
			$url = 'http://' . $this->domain . $path;
		}

		$result = new Tx_Extbase_Error_Result();

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

		if ($response === FALSE) {
			$result->forProperty('HTTP')->addError(new Tx_Extbase_Error_Error('HTTP Request for url ' . $this->domain . $path .' failed'));
			return $result;
		} else {
			$result->forProperty('HTTP')->addNotice(new Tx_Extbase_Error_Notice('HTTP Request worked'));
		}

		if ($expectedStatus) {
			$httpStatusCode = curl_getinfo($this->cUrl, CURLINFO_HTTP_CODE);
			if ($expectedStatus != $httpStatusCode) {
				$result->forProperty('STATUS')->addError(new Tx_Extbase_Error_Error('Wrong HTTP->Status ' . $httpStatusCode . ' (expected ' . $expectedStatus . ')'));
			} else {
				$result->forProperty('STATUS')->addNotice(new Tx_Extbase_Error_Notice('HTTP->Status is ' . $httpStatusCode . ' as expected'));
			}
		}

		if ($expectedRedirect) {

			if (strpos('http://', $expectedRedirect) !== 0 && strpos('https://', $expectedRedirect) !== 0) {
				$expectedRedirect = 'http://' . $this->domain . $expectedRedirect;
			}

			if ($responseHeaders['Location'] && $responseHeaders['Location'] == $expectedRedirect) {
				$result->forProperty('REDIRECT')->addNotice(new Tx_Extbase_Error_Notice('HTTP->Redirect is ' . $responseHeaders['Location'] . ' as expected'));
			} else if (!$responseHeaders['Location']) {
				$result->forProperty('REDIRECT')->addError(new Tx_Extbase_Error_Error('No HTTP->Redirect found (expected ' . $expectedRedirect . ')'));
			} else {
				$result->forProperty('REDIRECT')->addError(new Tx_Extbase_Error_Error('Wrong HTTP->Redirect ' . $responseHeaders['Location'] . ' (expected ' . $expectedRedirect . ')'));

			}
		}

		return $result;
	}

}
