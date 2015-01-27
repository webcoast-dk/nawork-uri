<?php

namespace Nawork\NaworkUri\Service;

class PathMonitorService {

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
	 * @param string $path
	 * @param int $expectedStatus
	 * @param null|string $expectedRedirect
	 * @param null|bool $expectedHttps use https
	 * @return \Nawork\NaworkUri\Domain\Model\PathTestResult
	 */
	public function testPath($path, $expectedStatus = NULL, $expectedRedirect = NULL, $expectedHttps = FALSE) {

		$url = 'http://' . $this->domain . $path;

		// create result object
		$pathTestResult = new \Nawork\NaworkUri\Domain\Model\PathTestResult();
		$pathTestResult->addInfo('expect'. $path . ' status:' . $expectedStatus . ' redirect:' . $expectedRedirect . ' https:' . $expectedHttps);
		$pathTestResult->setUrl($url);

		list($httpSuccess, $httpStatusCode, $httpLocation) = $this->executeCurlRequest($url);
		$pathTestResult->addInfo('first request (' . $url . ') success:'. $httpSuccess . ' status:' . $httpStatusCode . ' location:' . $httpLocation);


		if ($httpSuccess) {
			$pathTestResult->setSuccess(TRUE);
		} else {
			$pathTestResult->setSuccess(FALSE);
			return $pathTestResult;
		}

		// test status of first request if redirect or https is set
		if ($expectedRedirect || $expectedHttps) {
			if (!($httpStatusCode > 300 && $httpStatusCode < 400)) {
				$pathTestResult->setRedirectSuccess(FALSE);
				$pathTestResult->setStatus($httpStatusCode);
				$pathTestResult->setRedirect($httpLocation);
				return $pathTestResult;
			} else {
				list($secondHttpSuccess, $secondHttpStatusCode, $secondHttpLocation) = $this->executeCurlRequest($httpLocation);
				$httpSuccess = $secondHttpSuccess;
				$httpStatusCode = $secondHttpStatusCode;
				$pathTestResult->addInfo('second request (' . $httpLocation . ')success:'. $httpSuccess . ' status:' . $httpStatusCode . ' location:' . $secondHttpLocation);
			}
		}

		$pathTestResult->setStatus($httpStatusCode);
		$pathTestResult->setRedirect($httpLocation);

		// test status
		if ($expectedStatus) {

			if ($expectedStatus == $httpStatusCode) {
				$pathTestResult->setStatusSuccess(TRUE);
			} else {
				$pathTestResult->setStatusSuccess(FALSE);
			}
		}

		// https
		if (!$expectedRedirect && $expectedHttps == TRUE) {
			if ($httpLocation == 'https://' .  $this->domain . $path) {
				$pathTestResult->setHttpsSuccess(TRUE);
			} else {
				$pathTestResult->setHttpsSuccess(FALSE);
			}
		}

		// redirect
		if ($expectedRedirect) {
			if ($httpLocation == $expectedRedirect || $httpLocation == 'http://' .  $this->domain . $expectedRedirect) {
				$pathTestResult->setRedirectSuccess(TRUE);
			} else {
				$pathTestResult->setRedirectSuccess(FALSE);
			}
		}

		// https && redirect
		if ($expectedRedirect && $expectedHttps == TRUE) {

			if ($httpLocation == $expectedRedirect || $httpLocation == 'http://' .  $this->domain . $expectedRedirect || $httpLocation == 'https://' .  $this->domain . $expectedRedirect  ) {
				$pathTestResult->setRedirectSuccess(TRUE);
			} else {
				$pathTestResult->setRedirectSuccess(FALSE);
			}

			if (strpos($httpLocation, 'https://') === 0 ){
				$pathTestResult->setHttpsSuccess(TRUE);
			} else {
				$pathTestResult->setHttpsSuccess(FALSE);
			}
		}

		return $pathTestResult;
	}

	/**
	 * @param $url
	 */
	protected function executeCurlRequest($url) {
		curl_setopt($this->cUrl, CURLOPT_URL, $url);
		$response = curl_exec($this->cUrl);

		// success
		$httpSuccess = ($response === FALSE) ? FALSE : TRUE;

		// status
		$httpStatusCode = curl_getinfo($this->cUrl, CURLINFO_HTTP_CODE);

		// extract response headers
		list($header, $body) = explode("\n\n", $response, 2);
		$responseHeaders = array();
		$headerLines = explode("\n", $header);
		foreach($headerLines as $headerLine) {
			list($header, $value) = explode (': ', $headerLine, 2);
			if ($header && $value) {
				$responseHeaders[$header] = trim($value);
			}
		}
		$hhtpLocation = $responseHeaders['Location'];

		return array($httpSuccess, $httpStatusCode, $hhtpLocation);
	}
}
