<?php

namespace Nawork\NaworkUri\Install;


use TYPO3\CMS\Install\Updates\AbstractUpdate;

class FieldsV2xToV3Wizard extends AbstractUpdate
{
    protected $title = 'n@work URI: Old database fields to new ones';

    public function checkForUpdate(&$explanation)
    {
        if ($this->isWizardDone()) {
            return false;
        }

        $count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
            '*',
            'tx_naworkuri_uri',
            '(params!="" AND parameters="") OR (hash_path!="" AND path_hash="") OR (hash_params!="" AND parameters_hash="")'
        );
        $explanation = sprintf('There are %d urls to be migrated to the new database structure.', $count);

        if ($count === 0) {
            $this->markWizardAsDone();

            return false;
        }

        return true;
    }

    public function performUpdate(array &$databaseQueries, &$customOutput)
    {
        $result = true;
        $changedRows = 0;
        $errors = array();
        $queryResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'uid, params, hash_path, hash_params',
            'tx_naworkuri_uri',
            '(params!="" AND parameters="") OR (hash_path!="" AND path_hash="") OR (hash_params!="" AND parameters_hash="")'
        );
        if ($queryResult) {
            while ($record = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($queryResult)) {
                try {
                    $updateResult = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
                        'tx_naworkuri_uri',
                        'uid=' . (int)$record['uid'],
                        [
                            'parameters' => $record['params'],
                            'path_hash' => $record['hash_path'],
                            'parameters_hash' => $record['hash_params']
                        ]
                    );
                    if ($updateResult === true) {
                        ++$changedRows;
                    } else {
                        $result = false;
                        $errors[] = $GLOBALS['TYPO3_DB']->sql_error();
                    }
                } catch (\Exception $e) {
                    $result = false;
                    $errors[] = $e->getMessage();
                }
            }
        }

        if ($result) {
            $customOutput = sprintf(
                'Old url fields have been have been migrated: %d have been converted to new records.',
                $changedRows
            );
            $this->markWizardAsDone();
        } else {
            $customOutput = sprintf(
                'The execution was not successful. %d records have been changed. The following errors occured: <br /><br />%s',
                $changedRows,
                implode('<br /><br/>', $errors)
            );
        }

        return $result;
    }
}
