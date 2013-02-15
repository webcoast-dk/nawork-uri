<?php

class Tx_Naworkuri_Command_NaworkUriCommandController extends Tx_Extbase_MVC_Controller_CommandController {

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
	 * @param NULL|string $pathFile csv file to get the pathes (the first line is ignored, structure: path, __notes__, __notes___ , expected http-status, expected redirect target)
	 * @param NULL|string $outputErrors during execution
 	 * @param NULL|string $outputCSV output the result as csv to the given file
	 * @param bool $verbose show more informations during run
	 */
	public function monitorPathesCommand($domain = NULL, $user = NULL, $password = NULL, $sslNoVerify = FALSE, $pathFile = NULL, $outputErrors = FALSE, $outputCSV = NULL , $verbose = FALSE) {

		// get domain from extension-configuration if not given directly
		// $domain = 'tivoli.dev.work.de';

		// get http credentials from extension-configuration if not given directly
		// $user = NULL;
		// $password = NULL;
		// $sslNoVerify = TRUE;

		// create monitor service
		$urlMonitor = new \Tx_Naworkuri_Service_PathMonitorService($domain, $user, $password, $sslNoVerify);

		// find matching path-csv files

		$pathesTotal = 0;
		$pathesWithError = 0;
		$pathesWithStatusError = 0;
		$pathesWithRedirectError = 0;

		$outputCSVHandle = NULL;
		if (!is_null($outputCSV)) {
			if (file_exists($outputCSV) && is_writable($outputCSV)) {
				$outputCSVHandle = fopen($outputCSV, "w");
			} else if (!file_exists($outputCSV)) {
				$outputCSVHandle = fopen($outputCSV, "w");
			}
		}

		// run test for every file
		if (file_exists($pathFile)) {

			if ($verbose == TRUE || $outputErrors == TRUE ) {
				$this->outputLine();
				$this->outputLine("reading pathes from: " . $pathFile);
			}

			$inputPathFileHandle = fopen($pathFile, "r");

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

			if ($outputCSVHandle) {
				$resultColumnNumber = count($csvHeader);
				$messageColumnNumber = count($csvHeader) + 1;
				$csvHeader[$resultColumnNumber] = 'result';
				$csvHeader[$messageColumnNumber] = 'messages';
				fputcsv($outputCSVHandle, $csvHeader);
			}

			// read csv line by line and perform test
			while ($pathArray = fgetcsv($inputPathFileHandle)){
				$path = $pathArray[$pathColumnNumber];
				if ($path) {

					$pathesTotal ++;
					$expectedStatus = ($statusColumnNumber !== FALSE) ? $pathArray[$statusColumnNumber] : NULL;
					$expectedRedirect = ($redirectColumnNumber !== FALSE) ?  $pathArray[$redirectColumnNumber] : NULL;

					// perform test
					$pathResult = $urlMonitor->testPath($path, $expectedStatus, $expectedRedirect);

					$outputCsvStatus = '';
					$outputCsvMessage = '';

					if ($pathResult->hasErrors()) {
						$outputCsvStatus = 'ERROR';
						$pathesWithError ++;
						if ($pathResult->forProperty('STATUS')->hasErrors()) {
							$pathesWithStatusError ++;
						}
						if ($pathResult->forProperty('REDIRECT')->hasErrors()) {
							$pathesWithRedirectError ++;
						}
					} else {
						$outputCsvStatus = 'OK';
					}

					if ($verbose == TRUE || ($outputErrors == TRUE && $pathResult->hasErrors())) {
						$this->outputLine($pathesTotal . '. ' . $domain . $path);
						if ($verbose == TRUE) {
							$pathNoticesFlattened = $pathResult->getFlattenedNotices();
							foreach ($pathNoticesFlattened as $pathNotices) {
								foreach ($pathNotices as $pathNotice) {
									$this->outputLine( '  -> SUCCESS ' . $pathNotice->render());
								}
							}
						}

						if ($pathResult->hasErrors()) {
							$pathErrorsFlattened = $pathResult->getFlattenedErrors();
							$pathErrorMessages = array();
							foreach ($pathErrorsFlattened as $pathErrors) {
								foreach ($pathErrors as $pathError) {
									$this->outputLine( '  -> ERROR ' . $pathError->render());
									$pathErrorMessages[] = $pathError->render();
								}
							}
							$outputCsvMessage = implode (' : ', $pathErrorMessages);
						}

						// flush buffer to show results immediately
						$this->response->send();
						$this->response->setContent('');
					}

					if ($outputCSVHandle) {
						// add result columns
						$pathArray[$resultColumnNumber] = $outputCsvStatus;
						$pathArray[$messageColumnNumber] = $outputCsvMessage;
						fputcsv($outputCSVHandle, $pathArray);
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
		$this->outputLine(" - OK: " . ($pathesTotal - $pathesWithError));
		$this->outputLine(" - Errors: " . $pathesWithError);
		$this->outputLine(" - Status-Errors: " . $pathesWithStatusError);
		$this->outputLine(" - Redirect-Errors: " . $pathesWithRedirectError );

		$this->quit();
	}

}
