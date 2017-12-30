<?php

namespace Nawork\NaworkUri\Install;


use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\CMS\Install\Updates\AbstractUpdate;

class UpdateCacheHashWizard extends AbstractUpdate
{
    protected $title = 'n@work URI: Update cache hashes';

    public function checkForUpdate(&$explanation)
    {
        if ($this->isWizardDone()) {
            return false;
        }

        $count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'tx_naworkuri_uri', 'parameters LIKE "%cHash=%"');
        $explanation = sprintf('There are %d urls that contain a cHash parameter.', $count);

        if ($count === 0) {
            $fieldsResult = $GLOBALS['TYPO3_DB']->query('SHOW FIELDS FROM `tx_naworkuri_uri`');
            $hasOldParamsField = false;
            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($fieldsResult)) {
                if ($row['Field'] === 'params') {
                    $hasOldParamsField = true;
                    break;
                }
            }
            if ($hasOldParamsField) {
                $count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'tx_naworkuri_uri', 'params LIKE "%cHash=%"');
                if ($count === 0) {
                    // if $count is still 0, we can mark this done. Otherwise, this is an indication, that the migration to the new fields has not occurred yet, so we should just skip this one.
                    $this->markWizardAsDone();
                }
            }

            return false;
        }

        return true;
    }

    public function performUpdate(array &$databaseQueries, &$customOutput)
    {
        $result = true;
        $changedRows = 0;
        $queryResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'uid, page_uid, sys_language_uid, parameters',
            'tx_naworkuri_uri',
            'parameters LIKE "%cHash=%"'
        );
        $errors = [];
        if ($queryResult) {
            $cacheHashCalculator = GeneralUtility::makeInstance(CacheHashCalculator::class);
            while ($url = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($queryResult)) {
                try {
                    $parametersAsArray = \Nawork\NaworkUri\Utility\GeneralUtility::explode_parameters(
                        $url['parameters']
                    );
                    $parametersAsArray['id'] = $url['page_uid'];
                    $parametersAsArray['L'] = $url['sys_language_uid'];
                    $cacheHash = $cacheHashCalculator->generateForParameters(
                        \Nawork\NaworkUri\Utility\GeneralUtility::implode_parameters($parametersAsArray, false)
                    );
                    if ($parametersAsArray['cHash'] !== $cacheHash) {
                        unset($parametersAsArray['id']);
                        unset($parametersAsArray['L']);
                        $parametersAsArray['cHash'] = $cacheHash;
                        $parameterString = \Nawork\NaworkUri\Utility\GeneralUtility::implode_parameters(
                            $parametersAsArray,
                            false
                        );
                        $updateResult = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
                            'tx_naworkuri_uri',
                            'uid=' . (int)$url['uid'],
                            ['parameters' => $parameterString, 'parameters_hash' => md5($parameterString)]
                        );
                        if ($updateResult === true) {
                            ++$changedRows;
                        } else {
                            $result = false;
                            $errors[] = $GLOBALS['TYPO3_DB']->sql_error();
                        }
                    }
                } catch (\Exception $e) {
                    $result = false;
                    $errors[] = $e->getMessage();
                }
            }
        }

        if ($result) {
            $customOutput = sprintf(
                'Urls haven been checked and changed if necessary.',
                $changedRows
            );
            $this->markWizardAsDone();
        } else {
            $customOutput = sprintf(
                'The execution was not successful. %d records have been changed. The following errors occurred: <br /><br />%s',
                $changedRows,
                implode('<br /><br/>', $errors)
            );
        }

        return $result;
    }
}
