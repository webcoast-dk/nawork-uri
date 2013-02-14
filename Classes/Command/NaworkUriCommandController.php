<?php

class Tx_Naworkuri_Command_NaworkUriCommandController extends Tx_Extbase_MVC_Controller_CommandController {

	/**
	 * Monitor the stored pathes
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
			$csvHeader[5] = 'Test Result';
			$csvHeader[6] = 'Messages';

			if ($outputCSVHandle) {
				fputcsv($outputCSVHandle, $csvHeader);
			}

			// read csv line by line and perform test
			while ($pathArray = fgetcsv($inputPathFileHandle)){
				$path = $pathArray[0];
				if ($path) {
					$pathesTotal ++;
					$expectedStatus = $pathArray[3] ? $pathArray[3] : NULL;
					$expectedRedirect = $pathArray[4] ?  $pathArray[4] : NULL;

					// perform test
					$pathResult = $urlMonitor->testPath($path, $expectedStatus, $expectedRedirect);

					// add result columns
					$pathArray[5] = '';
					$pathArray[6] = '';

					if ($pathResult->hasErrors()) {
						$pathArray[5] = 'ERROR';
						$pathesWithError ++;
						if ($pathResult->forProperty('STATUS')->hasErrors()) {
							$pathesWithStatusError ++;
						}
						if ($pathResult->forProperty('REDIRECT')->hasErrors()) {
							$pathesWithRedirectError ++;
						}
					} else {
						$pathArray[5] = 'OK';
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
							$pathArray[6] = implode (' : ', $pathErrorMessages);
						}

						// flush buffer to show results immediately
						$this->response->send();
						$this->response->setContent('');
					}

					if ($outputCSVHandle) {
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
