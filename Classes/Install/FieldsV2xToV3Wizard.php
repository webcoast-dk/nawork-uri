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
        $hasOldFields = false;
        foreach ($connection->getSchemaManager()->listTableColumns('tx_naworkuri_uri') as $field) {
            if (in_array($field->getName(), ['params', 'hash_path', 'hash_params'])) {
                $hasOldFields = true;
            }
        }
        if ($hasOldFields) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_naworkuri_uri');
            $queryBuilder->count('uid')->from('tx_naworkuri_uri')->where($queryBuilder->expr()->orX(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->neq('params', ''),
                    $queryBuilder->expr()->eq('parameters', '')
                ),
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->neq('hash_path', ''),
                    $queryBuilder->expr()->eq('path_hash', '')
                ),
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->neq('hash_params', ''),
                    $queryBuilder->expr()->eq('parameters_hash', '')
                )
            ));
            $count = $queryBuilder->execute()->fetchColumn(0);
            $explanation = sprintf('There are %d urls to be migrated to the new database structure.', $count);

            if ($count === 0) {
                $this->markWizardAsDone();

                return false;
            }

            return true;
        }

        return false;
    }

    public function performUpdate(array &$databaseQueries, &$customOutput)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_naworkuri_uri');
        $queryBuilder->update('tx_naworkuri_uri')
            ->set('parameters', $queryBuilder->quoteIdentifier('params'), false)
            ->set('path_hash', $queryBuilder->quoteIdentifier('hash_path'), false)
            ->set('parameters_hash', $queryBuilder->quoteIdentifier('hash_params'), false)
            ->where($queryBuilder->expr()->orX(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->neq('params', ''),
                    $queryBuilder->expr()->eq('parameters', '')
                ),
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->neq('hash_path', ''),
                    $queryBuilder->expr()->eq('path_hash', '')
                ),
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->neq('hash_params', ''),
                    $queryBuilder->expr()->eq('parameters_hash', '')
                )
            ));
        $changedRows = $queryBuilder->execute();

        $customOutput = sprintf(
            'Old url fields have been have been migrated: %d have been converted to new records.',
            $changedRows
        );
        $this->markWizardAsDone();

        return true;
    }
}
