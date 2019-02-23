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

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_naworkuri_uri')
            ->count('uid')->from('tx_naworkuri_uri');
        $queryBuilder->where($queryBuilder->expr()->like('parameters', '%cHash=%'));
        $count = $queryBuilder->execute()->fetchColumn(0);
        $explanation = sprintf('There are %d urls that contain a cHash parameter.', $count);

        return $count !== 0;
    }

    public function performUpdate(array &$databaseQueries, &$customOutput)
    {
        $result = true;
        $changedRows = 0;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_naworkuri_uri')
            ->select(['uid', 'page_uid', 'sys_language_uid', 'parameters'])->from('tx_naworkuri_uri');
        $queryBuilder->where($queryBuilder->expr()->like('parameters', '%cHash=%'));
        $errors = [];
        if ($statement = $queryBuilder->execute()) {
            $cacheHashCalculator = GeneralUtility::makeInstance(CacheHashCalculator::class);
            foreach ($statement as $url) {
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
                        $queryBuilder->resetQueryParts();
                        $queryBuilder->update('tx_naworkuri_uri')
                            ->set('parameters', $parameterString)
                            ->set('parameters_hash', md5($parameterString))
                            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($url['uid'], \PDO::PARAM_INT)));
                        $changedRows += $queryBuilder->execute();
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
