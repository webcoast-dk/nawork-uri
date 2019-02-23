<?php

namespace Nawork\NaworkUri\Domain\Repository;

use Nawork\NaworkUri\Domain\Model\Domain;
use Nawork\NaworkUri\Domain\Model\Filter;
use Nawork\NaworkUri\Domain\Model\Language;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Description of UrlRepository
 *
 * @author thorben
 */
class UrlRepository extends Repository
{

    public function findUrlsByFilter(Filter $filter)
    {
        $query = $this->buildUrlQueryByFilter($filter);
        $query->setOffset((int)$filter->getOffset() * 100);
        $query->setLimit(100);

        return $query->setOrderings(['path' => QueryInterface::ORDER_ASCENDING])->execute();
    }

    public function countUrlsByFilter(Filter $filter)
    {
        $query = $this->buildUrlQueryByFilter($filter);

        return $query->count();
    }

    public function findRedirectsByFilter(Filter $filter)
    {
        $query = $this->buildRedirectQueryByFilter($filter);
        $query->setOffset((int)$filter->getOffset() * 100);
        $query->setLimit(100);

        return $query->setOrderings(['path' => QueryInterface::ORDER_ASCENDING])->execute();
    }

    public function countRedirectsByFilter(Filter $filter)
    {
        $query = $this->buildRedirectQueryByFilter($filter);

        return $query->count();
    }

    public function deleteByUids($uids)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_naworkuri_uri');
        $queryBuilder->delete('tx_naworkuri_uri');
        foreach ($uids as $uid) {
            $queryBuilder->orWhere($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)));
        }
        $queryBuilder->execute();
    }

    private function getPidsRecursive($id, &$pids, $depth = 0)
    {
        $rows = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages')
            ->select(['uid'], 'pages', ['pid' => (int)$pid, 'deleted' => 0])
            ->fetchAll();
        foreach ($rows as $page) {
            $pids[] = $page['uid'];
            $this->getPidsRecursive($page['uid'], $pids, ($depth + 1));
        }
    }

    /**
     *
     * @param \Nawork\NaworkUri\Domain\Model\Filter $filter
     *
     * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
     */
    private function buildUrlQueryByFilter(Filter $filter)
    {
        $query = $this->createQuery();
        // ignore language, because all urls should be selected
        $query->getQuerySettings()->setRespectSysLanguage(false);

        $constraints = [];

        if ($filter->getDomain() instanceof Domain) {
            $constraints[] = $query->equals('domain', $filter->getDomain());
        }

        if (!$filter->getIgnoreLanguage()) {
            if (!$filter->getLanguage() instanceof Language) {
                $constraints[] = $query->equals('sysLanguageUid', 0);
            } else {
                $constraints[] = $query->equals('sysLanguageUid', $filter->getLanguage());
            }
        }

        if (count($filter->getTypes()) > 0) {
            $typeConstraints = [];
            if (in_array('normal', $filter->getTypes())) {
                $typeConstraints[] = $query->logicalAnd([$query->equals('type', 0), $query->equals('locked', 0)]);
            }

            if (in_array('old', $filter->getTypes())) {
                $typeConstraints[] = $query->equals('type', 1);
            }

            if (in_array('locked', $filter->getTypes())) { // if we show locked, set locked=1 and type=0, because only type 0 urls can be locked
                $typeConstraints[] = $query->logicalAnd([$query->equals('locked', 1), $query->equals('type', 0)]);
            }
            if (count($typeConstraints) > 0) {
                $constraints[] = $query->logicalOr($typeConstraints);
            }
        }
        switch ($filter->getScope()) {
            case 'subpages':
                $pidConstraints = [];
                $pageIds = [$filter->getPageId()];
                $this->getPidsRecursive($filter->getPageId(), $pageIds);
                foreach ($pageIds as $pid) {
                    $pidConstraints[] = $query->equals('pageUid', $pid);
                }
                if (count($pidConstraints) > 0) {
                    $constraints[] = $query->logicalOr($pidConstraints);
                }
                break;
            case 'global':
                break;
            case 'page':
            default:
                $constraints[] = $query->equals('pageUid', $filter->getPageId());
                break;
        }
        $path = $filter->getPath();
        if (!empty($path)) {
            $constraints[] = $query->like('path', str_replace('*', '%', $path));
        }
        $parameters = $filter->getParameters();
        if (!empty($parameters)) {
            $constraints[] = $query->like('parameters', str_replace('*', '%', $parameters));
        }
        $constraints[] = $query->logicalAnd([$query->logicalNot($query->equals('type', 2)), $query->logicalNot($query->equals('type', 3))]); // we do not want redirects in this result
        if (count($constraints) > 0) {
            $query = $query->matching($query->logicalAnd($constraints));
        }

        return $query;
    }

    private function buildRedirectQueryByFilter(Filter $filter)
    {
        $query = $this->createQuery();
        // ignore language, because all urls should be selected
        $query->getQuerySettings()->setRespectSysLanguage(false);

        $constraints = [];

        if ($filter->getDomain() instanceof Domain) {
            $constraints[] = $query->equals('domain', $filter->getDomain());
        }

        $path = $filter->getPath();
        if (!empty($path)) {
            $constraints[] = $query->like('path', str_replace('*', '%', $path));
        }
        $constraints[] = $query->logicalOr([$query->equals('type', 2), $query->equals('type', 3)]); // we want only redirects in this result
        if (count($constraints) > 0) {
            $query = $query->matching($query->logicalAnd($constraints));
        }

        return $query;
    }

}
