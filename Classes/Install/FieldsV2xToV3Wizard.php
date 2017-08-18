<?php

namespace Nawork\NaworkUri\Install;


use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\AbstractUpdate;

class FieldsV2xToV3Wizard extends AbstractUpdate
{
    protected $title = 'n@work URI: Old database fields to new ones';

    public function checkForUpdate(&$explanation)
    {
        if ($this->isWizardDone()) {
            return false;
        }

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_naworkuri_uri');
        $query = $connection->createQueryBuilder()->count('*')->from('tx_naworkuri_uri');
        $query->getRestrictions()->removeAll();
        $count = $query->where(
            $query->expr()->orX(
                $query->expr()->andX(
                    'params!=""',
                    'parameters=""'
                ),
                $query->expr()->andX(
                    'hash_path!=""',
                    'path_hash=""'
                ),
                $query->expr()->andX(
                    'hash_params!=""',
                    'parameters_hash=""'
                )
            )
        )->execute()->fetchColumn(0);
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
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_naworkuri_uri');
        $query = $connection->createQueryBuilder()
            ->select('uid', 'params', 'hash_path', 'hash_params')
            ->from('tx_naworkuri_uri');
        $query->getRestrictions()->removeAll();
        $query->where(
            $query->expr()->orX(
                $query->expr()->andX(
                    'params!=""',
                    'parameters=""'
                ),
                $query->expr()->andX(
                    'hash_path!=""',
                    'path_hash=""'
                ),
                $query->expr()->andX(
                    'hash_params!=""',
                    'parameters_hash=""'
                )
            )
        );
        if ($statement = $query->execute()) {
            $statement->setFetchMode(\PDO::FETCH_ASSOC);
            foreach ($statement as $record) {
                try {
                    $updateQuery = $connection->createQueryBuilder()->update('tx_naworkuri_uri')
                        ->set('parameters', $record['params'])
                        ->set('path_hash', $record['hash_path'])
                        ->set('parameters_hash', $record['hash_params'])
                        ->where('uid=' . (int)$record['uid']);
                    $databaseQueries[] = $updateQuery->getSQL();
                    $affectedRows = $updateQuery->execute();
                    if ($affectedRows === 1) {
                        ++$changedRows;
                    } else {
                        $result = false;
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

    private function getNewLayoutValue($oldValue)
    {
        switch ($oldValue) {
            case 1:
                return 'typo3_base_setup__igcitysued_resources-homepage';
            case 2:
                return 'typo3_base_setup__igcitysued_resources-2columns';
            case 3:
                return 'typo3_base_setup__igcitysued_resources-1column';
            default:
                return '';
        }
    }
}
