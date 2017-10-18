<?php

namespace Nawork\NaworkUri\Install;


use TYPO3\CMS\Core\Database\ConnectionPool;
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

        /** @var \TYPO3\CMS\Core\Database\Connection $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_naworkuri_uri');
        $query = $connection->createQueryBuilder()->count('*')->from('tx_naworkuri_uri');
        $query->getRestrictions()->removeAll();
        $count = $query->where(
            $query->expr()->like('parameters', $query->quote('%cHash=%', \PDO::PARAM_STR))
        )->execute()->fetchColumn(0);
        $explanation = sprintf('There are %d urls that contain a cHash parameter.', $count);

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
            ->select('uid', 'page_uid', 'sys_language_uid', 'parameters')
            ->from('tx_naworkuri_uri');
        $query->getRestrictions()->removeAll();
        $query->where(
            $query->expr()->like('parameters', $query->quote('%cHash=%', \PDO::PARAM_STR))
        );
        $errors = [];
        if ($statement = $query->execute()) {
            $statement->setFetchMode(\PDO::FETCH_ASSOC);

		    $cacheHashCalculator = GeneralUtility::makeInstance(CacheHashCalculator::class);
            foreach ($statement as $url) {
                try {
                    $parametersAsArray = \Nawork\NaworkUri\Utility\GeneralUtility::explode_parameters($url['parameters']);
                    $parametersAsArray['id'] = $url['page_uid'];
                    $parametersAsArray['L'] = $url['sys_language_uid'];
                    $cacheHash = $cacheHashCalculator->generateForParameters(
                        \Nawork\NaworkUri\Utility\GeneralUtility::implode_parameters($parametersAsArray, FALSE)
                    );
                    if ($parametersAsArray['cHash'] !== $cacheHash) {
                        unset($parametersAsArray['id']);
                        unset($parametersAsArray['L']);
                        $parametersAsArray['cHash'] = $cacheHash;
                        $parameterString = \Nawork\NaworkUri\Utility\GeneralUtility::implode_parameters(
                            $parametersAsArray,
                            FALSE
                        );
                        $affectedRows = $connection->update(
                            'tx_naworkuri_uri',
                            ['parameters' => $parameterString, 'parameters_hash' => md5($parameterString)],
                            ['uid' => $url['uid']]
                        );
                        if ($affectedRows === 1) {
                            ++$changedRows;
                        } else {
                            $result = false;
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
