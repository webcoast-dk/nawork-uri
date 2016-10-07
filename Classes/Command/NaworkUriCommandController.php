<?php

namespace Nawork\NaworkUri\Command;

use Nawork\NaworkUri\Service\PathMonitorService;

class NaworkUriCommandController extends \TYPO3\CMS\Extbase\Mvc\Controller\CommandController {

	/**
	 * Monitor the stored pathes
	 *
	 * The pathes are read from a csv file that contains the columns path, status and redirect.
	 * The first line of the csv file is used to identify the different columns. Additional columns
	 * are allowed. If the result is exported as csv the original csv values will be preserved and
	 * the columns result and message are appended.
	 *
	 * @param NULL|string $domain domain for testing the pathes
	 * @param NULL|string $user http-user (leave empty if no http-auth is needed)
	 * @param NULL|string $password http-basic password (leave empty if no http-auth is needed)
	 * @param bool $sslNoVerify don not veryfy ssl-peer
	 * @param NULL|string $pathes csv file to get the pathes (the first line is ignored, structure: path, __notes__, __notes___ , expected http-status, expected redirect target)
	 * @param NULL|boolean $errors during execution
 	 * @param NULL|string $output output the result as csv to the given file
	 * @param bool $verbose show more informations during run
	 * @param int $sleep sleep the given number of seconds after each test to protect the tested server
	 */
	public function monitorPathesCommand($domain = NULL, $user = NULL, $password = NULL, $sslNoVerify = FALSE, $pathes = NULL, $errors = FALSE, $output = NULL , $verbose = FALSE, $sleep = 0) {

		$urlMonitor = new PathMonitorService($domain, $user, $password, $sslNoVerify);

		$pathesTotal = 0;
		$pathesOk = 0;
		$pathesWithError = 0;
		$pathesWithStatusError = 0;
		$pathesWithRedirectError = 0;
		$pathesWithHttpsError = 0;


		if (file_exists($pathes)) {

			if ($verbose == TRUE || $errors == TRUE ) {
				$this->outputLine();
				$this->outputLine("reading pathes from: " . $pathes);
			}

			$inputPathFileHandle = fopen($pathes, "r");

			// ignore first line
			$csvHeader = fgetcsv($inputPathFileHandle);

			// detect column numbers from first csv line
			$pathColumnNumber = array_search('path', $csvHeader);
			if ($pathColumnNumber !== FALSE) {
				$this->outputLine(" - reading status from column " . $pathColumnNumber);
			} else {
				$this->outputLine("No path column found. I quit now.");
				$this->quit();
			}

			$statusColumnNumber = array_search('status', $csvHeader);
			if ($statusColumnNumber !== FALSE) {
				$this->outputLine(" - reading status from column " . $statusColumnNumber);
			}

			$redirectColumnNumber = array_search('redirect', $csvHeader);
			if ($redirectColumnNumber !== FALSE) {
				$this->outputLine(" - reading redirects from column " . $redirectColumnNumber);
			}

			$httpsColumnNumber = array_search('https', $csvHeader);
			if ($httpsColumnNumber !== FALSE) {
				$this->outputLine(" - reading force-https from column " . $httpsColumnNumber);
			}

			$outputCSVHandle = NULL;
			if (!is_null($output)) {
				if (file_exists($output) && is_writable($output)) {
					$outputCSVHandle = fopen($output, "w");
				} else if (!file_exists($output)) {
					$outputCSVHandle = fopen($output, "w");
				}

				if ($outputCSVHandle) {
					$resultSuccessColumnNumber = count($csvHeader);
					$resultStatusColumnNumber = count($csvHeader) + 1;
					$resultRedirectColumnNumber = count($csvHeader) + 2;
					$resultHttpsColumnNumber = count($csvHeader) + 3;
					$csvHeader[$resultSuccessColumnNumber] = 'result-sucess';
					$csvHeader[$resultStatusColumnNumber] = 'result-status';
					$csvHeader[$resultRedirectColumnNumber] = 'result-redirect';
					$csvHeader[$resultHttpsColumnNumber] = 'result-https';
					fputcsv($outputCSVHandle, $csvHeader);
				}
			}

			$this->outputLine();

			// read csv line by line and perform test
			while ($pathArray = fgetcsv($inputPathFileHandle)){

				// flush buffer to show results immediately
				$this->response->send();
				$this->response->setContent('');

				$path = $pathArray[$pathColumnNumber];
				if ($path) {

					$pathesTotal ++;
					// if ($pathesTotal > 10) break;

					$expectedStatus = ($statusColumnNumber !== FALSE) ? $pathArray[$statusColumnNumber] : NULL;
					$expectedRedirect = ($redirectColumnNumber !== FALSE) ?  $pathArray[$redirectColumnNumber] : NULL;
					$expectedHttps = ($httpsColumnNumber !== FALSE) ?  $pathArray[$httpsColumnNumber] : FALSE;

					// perform test
					$pathTestResult = $urlMonitor->testPath($path, $expectedStatus, $expectedRedirect, $expectedHttps);

					if ($pathTestResult->getSuccess() == TRUE)  {
						$pathesOk ++;
					} else {
						$pathesWithError ++;

						if ($pathTestResult->getStatusSuccess() === FALSE) {
							$pathesWithStatusError ++;
						}

						if ($pathTestResult->getRedirectSuccess() === FALSE)  {
							$pathesWithRedirectError ++;
						}

						if ($pathTestResult->getHttpsSuccess() === FALSE)  {
							$pathesWithHttpsError ++;
						}

					}


					// create cli output
					if ($verbose == TRUE || $pathTestResult->getSuccess() == FALSE) {

						$this->outputLine($pathesTotal . '. ' . $domain . $path);

						if ($expectedStatus) {
							if ($verbose == TRUE || $pathTestResult->getStatusSuccess() === FALSE) {
								$statusSuccess = $pathTestResult->getStatusSuccess();
								switch (TRUE) {
									default:
										$this->outputLine('   - INFO: Status is not determined');
										break;
									case ($statusSuccess === TRUE):
										$this->outputLine('   - OK: Status is ' . $pathTestResult->getStatus() . ' as expected ');
										break;
									case ($statusSuccess === FALSE):
										$this->outputLine('   - ERROR: Status is ' . $pathTestResult->getStatus() . ' but ' . $expectedStatus . ' is expected');
										break;
								}
							}
						}

						if ($expectedRedirect) {
							if ($verbose == TRUE || $pathTestResult->getRedirectSuccess() === FALSE) {
								$redirectSuccess = $pathTestResult->getRedirectSuccess();
								// print_r(array($pathTestResult,$redirectSuccess,($redirectSuccess === TRUE)));
								switch (TRUE) {
									default:
										$this->outputLine('   - INFO: Redirect is not determined');
										break;
									case ($redirectSuccess === TRUE):
										$this->outputLine('   - OK: Redirect is ' . $pathTestResult->getRedirect() . ' as expected');
										break;
									case ($redirectSuccess === FALSE):
										$this->outputLine('   - ERROR: Redirect is ' . $pathTestResult->getRedirect() . ' but ' . $expectedRedirect . ' is expected');
										break;
								}

							}
						}

						if ($expectedHttps == TRUE) {
							if ($verbose == TRUE || $pathTestResult->getHttpsSuccess() === FALSE) {
								$httpsSuccess = $pathTestResult->getHttpsSuccess();
								switch (TRUE) {
									default:
										$this->outputLine('   - INFO: Https is not determined');
										break;
									case ($httpsSuccess === TRUE):
										$this->outputLine('   - OK: Https is found');
										break;
									case ($httpsSuccess === FALSE):
										$this->outputLine('   - ERROR: Https is not found');
										break;
								}
							}
						}
					}

					// write csv if any is given
					if ($outputCSVHandle) {
						$pathArray[$resultSuccessColumnNumber] = '' . $pathTestResult->getSuccess() ? 'OK' : 'ERROR';
						$pathArray[$resultStatusColumnNumber] = '' . $pathTestResult->getStatus();
						$pathArray[$resultRedirectColumnNumber] = '' . $pathTestResult->getRedirect();
						$pathArray[$resultHttpsColumnNumber] = '' . $pathTestResult->getHttpsSuccess();
						fputcsv($outputCSVHandle, $pathArray);
					}

					// sleep the given number of seconds
					if ($sleep > 0) {
						sleep($sleep);
					}
				}
			}

			if ($inputPathFileHandle) {
				fclose($inputPathFileHandle);
			}

			if ($outputCSVHandle) {
				fclose($outputCSVHandle);
			}
		}

			// show total result
		$this->outputLine();
		$this->outputLine("Total results for " . $pathesTotal . ' pathes:');
		$this->outputLine(" - OK: " . $pathesOk);
		$this->outputLine(" - Errors: " . $pathesWithError);
		$this->outputLine(" - Status-Errors: " . $pathesWithStatusError);
		$this->outputLine(" - Redirect-Errors: " . $pathesWithRedirectError );
		$this->outputLine(" - HTTPS-Errors: " . $pathesWithHttpsError );

		$this->quit();
	}

}
