<?php

namespace Nawork\NaworkUri\Install;


use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\Renderer\BootstrapRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\AbstractUpdate;

class AssignDomainWizard extends AbstractUpdate
{
    protected $title = 'n@work URI: Assign domain to urls records without domain';

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
                'domain=""',
                'domain=0'
            )
        )->execute()->fetchColumn(0);
        $explanation = sprintf('There are %d urls without an assigned domain.', $count);

        if ($count === 0) {
            $this->markWizardAsDone();

            return false;
        }

        return true;
    }

    public function getUserInput($inputPrefix)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_domain');
        $query = $connection->createQueryBuilder()->select('uid', 'domainName')->from('sys_domain');
        $query->where('tx_naworkuri_masterDomain=\'\'');
        $domainOptions = [];
        if ($statement = $query->execute()) {
            $statement->setFetchMode(\PDO::FETCH_ASSOC);
            foreach($statement as $domainRecord) {
                $domainOptions[] = sprintf('<option value="%d">%s</option>', $domainRecord['uid'], $domainRecord['domainName']);
            }
        }
        if (count($domainOptions) > 0) {
            return sprintf('<p>Assign not-assigned domains to the following domain:</p><select name="%s">%s</select>', $inputPrefix . '[domain]', implode('', $domainOptions));
        } else {
            return '
            <div class="typo3-messages">
                <div class="alert alert-danger">
                    <div class="media">
                        <div class="media-body">
                            <h4 class="alert-title">No domain records</h4>
                            <p class="alert-message">There are no domain records to select. Please create at least one domain record and run this wizard again.</p>
                        </div>
                    </div>
                </div>
            </div>
            ';
        }
    }

    public function checkUserInput(&$wizardInputErrorMessage)
    {
        $requestParameters = GeneralUtility::_GP('install');
        if (!isset($requestParameters['values'][$requestParameters['values']['identifier']]['domain'])) {
            $wizardInputErrorMessage = 'No domain given. This is probably because there are no domain records. Please create at least one domain record and run this wizard again.';
            return false;
        }
        return true;
    }

    public function performUpdate(array &$databaseQueries, &$customOutput)
    {
        $result = true;
        $changedRows = 0;
        $requestParameters = GeneralUtility::_GP('install');
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_naworkuri_uri');
        $query = $connection->createQueryBuilder()->update('tx_naworkuri_uri')->set('domain', $requestParameters['values'][$requestParameters['values']['identifier']]['domain']);
        $query->where($query->expr()->orX('domain=\'\'', 'domain=0', 'domain=\'0\''));
        try {
            $changedRows = $query->execute();
        } catch (\Exception $e) {
            $result = false;
            $errors[] = $e->getMessage();
        }

        if ($result) {
            $customOutput = sprintf(
                'Urls without domain have been updated: %d have been assigned the given domain.',
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
