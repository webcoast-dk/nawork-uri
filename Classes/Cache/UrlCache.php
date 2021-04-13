<?php

namespace Nawork\NaworkUri\Cache;

use Doctrine\DBAL\DBALException;
use Nawork\NaworkUri\Configuration\TableConfiguration;
use Nawork\NaworkUri\Exception\DbErrorException;
use Nawork\NaworkUri\Exception\UrlIsNotUniqueException;
use Nawork\NaworkUri\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendGroupRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UrlCache
{

    const URI_TYPE_NORMAL = 0;
    const URI_TYPE_OLD = 1;
    const URI_TYPE_REDIRECT_PATH = 2;
    const URI_TYPE_REDIRECT_PAGE = 3;

    private $timeout = 86400;
    /**
     *
     * @var \Nawork\NaworkUri\Configuration\TableConfiguration
     */
    private $tableConfiguration;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->tableConfiguration = GeneralUtility::makeInstance(TableConfiguration::class);
    }

    /**
     * Set the timeout for the url cache validity
     *
     * @param int $time Number of seconds the url should be valid, defaults to 86400 (= one day)
     */
    public function setTimeout($time = 86400)
    {
        $this->timeout = $time;
    }

    /**
     * Find a url based on the parameters and the domain
     *
     * @param array   $params
     * @param string  $domain
     * @param integer $language
     * @param boolean $ignoreTimeout
     *
     * @return boolean|array
     */
    public function findCachedUrl($params, $domain, $language, $ignoreTimeout)
    {
        $uid = (int)$params['id'];
        unset($params['id']);
        unset($params['L']);
        /* evaluate the cache timeout */
        $pageStatement = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->tableConfiguration->getPageTable())->select(['*'], $this->tableConfiguration->getPageTable(), ['uid' => intval($uid)], [], [], 1);
        if ($pageStatement->rowCount() === 1) {
            $page = $pageStatement->fetch();
            if ($page['cache_timeout'] > 0) {
                $this->setTimeout($page['cache_timeout']);
            } elseif ($GLOBALS['TSFE']->config['config']['cache_period'] > 0) {
                $this->setTimeout($GLOBALS['TSFE']->config['config']['cache_period']);
            } else {
                $this->setTimeout(); // set to default, should be 86400 (24 hours)
            }
            $urlQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableConfiguration->getUrlTable());
            $urlQueryBuilder->select('u.path')->from($this->tableConfiguration->getUrlTable(), 'u')
                ->join('u', $this->tableConfiguration->getPageTable(), 'p', 'u.page_uid = p.uid')
                ->where(
                    $urlQueryBuilder->expr()->eq('u.sys_language_uid', $urlQueryBuilder->createNamedParameter($language, \PDO::PARAM_INT)),
                    $urlQueryBuilder->expr()->eq('u.parameters_hash', $urlQueryBuilder->createNamedParameter(md5(\Nawork\NaworkUri\Utility\GeneralUtility::implode_parameters($params, false)), \PDO::PARAM_STR)),
                    $urlQueryBuilder->expr()->eq('u.domain', $urlQueryBuilder->createNamedParameter($domain, \PDO::PARAM_INT)),
                    $urlQueryBuilder->expr()->eq('u.page_uid', $urlQueryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)),
                    $urlQueryBuilder->expr()->eq('u.type', $urlQueryBuilder->createNamedParameter(self::URI_TYPE_NORMAL, \PDO::PARAM_INT))
                );
            $urlQueryBuilder->getRestrictions()->removeAll();
            /* if there is no be user logged in, hidden or time controlled non visible pages should not return a url */
            if (!\Nawork\NaworkUri\Utility\GeneralUtility::isActiveBeUserSession()) {
                $urlQueryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
            } else {
                $urlQueryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            }
            if (!$ignoreTimeout) {
                $urlQueryBuilder->andWhere($urlQueryBuilder->expr()->orX(
                    $urlQueryBuilder->expr()->gt('u.tstamp', time() - $this->timeout),
                    $urlQueryBuilder->expr()->eq('u.locked', 1)
                ));
            }
            $urlQueryBuilder->setMaxResults(1);
            if ($statement = $urlQueryBuilder->execute()) {
                if ($statement->rowCount() === 1) {
                    $url = $statement->fetch();

                    return $url['path'];
                }
            }
        }

        return false;
    }

    /**
     * Find an existing url based on the page's id, language, parameters and domain.
     * This is used to get an url that's cache time has expired but is a normal url.
     *
     * @param integer    $page
     * @param integer    $language
     * @param array      $params
     * @param string     $path
     * @param int|string $domain
     *
     * @return array|boolean
     */
    public function findExistantUrl($page, $language, $params, $path, $domain)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableConfiguration->getUrlTable());
        $queryBuilder->select('*')->from($this->tableConfiguration->getUrlTable(), 'u')
            ->where(
                $queryBuilder->expr()->eq('domain', $queryBuilder->createNamedParameter($domain, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('page_uid', $queryBuilder->createNamedParameter($page, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($language, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('parameters_hash', $queryBuilder->createNamedParameter(md5(\Nawork\NaworkUri\Utility\GeneralUtility::implode_parameters($params, false)), \PDO::PARAM_STR)),
                $queryBuilder->expr()->eq('path_hash', $queryBuilder->createNamedParameter(md5($path), \PDO::PARAM_STR)),
                $queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            )
            ->setMaxResults(1);
        $statement = $queryBuilder->execute();
        if ($statement->rowCount() === 1) {
            return $statement->fetch();
        }

        return false;
    }

    /**
     * Find an old url based on the domain and path. It will be reused with new parameters.
     * If no old url is found, this function looks for a url on a hidden or deleted page.
     *
     * @param string $domain
     * @param string $path
     *
     * @return array|boolean
     */
    public function findOldUrl($domain, $path)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableConfiguration->getUrlTable());
        // Look for an old url (type = 1) with the given domain and the given path
        $queryBuilder->select('*')->from($this->tableConfiguration->getUrlTable())->where(
            $queryBuilder->expr()->eq('domain', $queryBuilder->createNamedParameter($domain, \PDO::PARAM_INT)),
            $queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter(self::URI_TYPE_OLD, \PDO::PARAM_INT)),
            $queryBuilder->expr()->eq('path_hash', $queryBuilder->createNamedParameter(md5($path), \PDO::PARAM_STR))
        )->setMaxResults(1);
        $statement = $queryBuilder->execute();
        if ($statement->rowCount() === 1) {
            return $statement->fetch();
        }

        // Look for a normal url (type = 0) on any deleted or hidden page
        $queryBuilder->resetQueryParts();
        $queryBuilder->select('*')->from($this->tableConfiguration->getUrlTable(), 'u')
            ->join('u', $this->tableConfiguration->getPageTable(), 'p', 'p.uid = u.page_uid')
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq('p.deleted', 1),
                    $queryBuilder->expr()->eq('p.hidden', 1)
                ),
                $queryBuilder->expr()->eq('u.domain', $queryBuilder->createNamedParameter($domain, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('u.path_hash', $queryBuilder->createNamedParameter(md5($path), \PDO::PARAM_STR))
            )
            ->setMaxResults(1);
        $statement = $queryBuilder->execute();
        if ($statement->rowCount() === 1) {
            return $statement->fetch();
        }

        return false;
    }

    /**
     *
     * @param array   $parameters
     * @param string  $domain
     * @param integer $language
     * @param string  $path
     *
     * @return string
     * @throws UrlIsNotUniqueException
     */
    public function writeUrl($parameters, $domain, $language, $path)
    {
        $orginalParameters = $parameters;
        $pageUid = intval($parameters['id']);
        $language = intval($language ? $language : 0);
        unset($parameters['id']);
        unset($parameters['L']);
        /* try to find an existing url that was too old to be retreived from cache */
        $existingUrl = $this->findExistantUrl($pageUid, $language, $parameters, $path, $domain);
        if ($existingUrl !== false) {
            $this->touchUrl($existingUrl['uid']);
            $this->makeOldUrl($domain, $pageUid, $language, $parameters, $existingUrl['uid']);

            return $existingUrl['path'];
        }
        /* try find an old url that could be reactivated */
        $existingUrl = $this->findOldUrl($domain, $path);
        if ($existingUrl != false) {
            $this->makeOldUrl($domain, $pageUid, $language, $parameters, $existingUrl['uid']);
            $this->updateUrl($existingUrl['uid'], $pageUid, $language, $parameters);

            return $path;
        }
        /* if we also did not find a url here we must create it */
        $this->makeOldUrl($domain, $pageUid, $language, $parameters);
        $uniquePath = $this->unique($pageUid, $language, $path, $parameters, $domain); // make the url unique
        if ($uniquePath === false) {
            throw new UrlIsNotUniqueException($path, $domain, $orginalParameters, $language);
        }
        /* try to find an existing url that was too old to be retreived from cache */
        $existingUrl = $this->findExistantUrl($pageUid, $language, $parameters, $uniquePath, $domain);
        if ($existingUrl !== false) {
            $this->touchUrl($existingUrl['uid']);
            $this->makeOldUrl($domain, $pageUid, $language, $parameters, $existingUrl['uid']);

            return $existingUrl['path'];
        }
        /* try find an old url that could be reactivated */
        $existingUrl = $this->findOldUrl($domain, $uniquePath);
        if ($existingUrl != false) {
            $this->makeOldUrl($domain, $pageUid, $language, $parameters, $existingUrl['uid']);
            $this->updateUrl($existingUrl['uid'], $pageUid, $language, $parameters);

            return $uniquePath;
        }
        $this->createUrl($pageUid, $language, $domain, $parameters, $uniquePath, $path);

        return $uniquePath;
    }

    /**
     * Read Cache entry for the given URI
     *
     * @param string $path   URI Path
     * @param string $domain Current Domain
     *
     * @return array|boolean cache result
     */
    public function read_path($path, $domain)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableConfiguration->getUrlTable());
        $queryBuilder->select('u.*')->from($this->tableConfiguration->getUrlTable(), 'u')
            ->join('u', $this->tableConfiguration->getPageTable(), 'p', $queryBuilder->expr()->orX(
                $queryBuilder->expr()->eq('u.page_uid', 'p.uid'),
                $queryBuilder->expr()->in('u.type', [self::URI_TYPE_REDIRECT_PATH, self::URI_TYPE_REDIRECT_PAGE])
            ))
            ->where(
                $queryBuilder->expr()->eq('u.path_hash', $queryBuilder->createNamedParameter(md5($path), \PDO::PARAM_STR)),
                $queryBuilder->expr()->eq('u.domain', $queryBuilder->createNamedParameter($domain, \PDO::PARAM_INT))
            );
        $queryBuilder->getRestrictions()->removeAll();
        if (!\Nawork\NaworkUri\Utility\GeneralUtility::isActiveBeUserSession()) {
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
//            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(FrontendWorkspaceRestriction::class));
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(HiddenRestriction::class));
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(StartTimeRestriction::class));
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(EndTimeRestriction::class));
//            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(FrontendGroupRestriction::class));
        } else {
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        }
        $queryBuilder->setMaxResults(1);
        if ($statement = $queryBuilder->execute()) {
            if ($statement->rowCount() === 1) {
                return $statement->fetch();
            }
        }

        return false;
    }

    /**
     *
     * @param integer $page
     * @param integer $language
     * @param string  $domain
     * @param array   $parameters
     * @param string  $path
     * @param string  $originalPath
     *
     * @throws DbErrorException
     */
    public function createUrl($page, $language, $domain, $parameters, $path, $originalPath)
    {
        $parameters = \Nawork\NaworkUri\Utility\GeneralUtility::implode_parameters($parameters, false);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableConfiguration->getUrlTable());
        $queryBuilder->insert($this->tableConfiguration->getUrlTable())->values(
            [
                'page_uid' => $queryBuilder->createNamedParameter($page, \PDO::PARAM_INT),
                'tstamp' => $queryBuilder->createNamedParameter(time(), \PDO::PARAM_INT),
                'crdate' => $queryBuilder->createNamedParameter(time(), \PDO::PARAM_INT),
                'sys_language_uid' => $queryBuilder->createNamedParameter($language, \PDO::PARAM_INT),
                'domain' => $queryBuilder->createNamedParameter($domain, \PDO::PARAM_INT),
                'path' => $queryBuilder->createNamedParameter($path, \PDO::PARAM_STR),
                'path_hash' => $queryBuilder->createNamedParameter(md5($path), \PDO::PARAM_STR),
                'parameters' => $queryBuilder->createNamedParameter($parameters, \PDO::PARAM_STR),
                'parameters_hash' => $queryBuilder->createNamedParameter(md5($parameters), \PDO::PARAM_STR),
                'original_path' => $queryBuilder->createNamedParameter($originalPath, \PDO::PARAM_STR)
            ],
            false
        );
        try {
            $queryBuilder->execute();
        } catch (DBALException $e) {
            throw new DbErrorException($e->getMessage());
        }
    }

    private function touchUrl($uid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableConfiguration->getUrlTable());
        $queryBuilder->update($this->tableConfiguration->getUrlTable())
            ->set('tstamp', $queryBuilder->createNamedParameter(time(), \PDO::PARAM_INT), false)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)))
            ->execute();
    }

    /**
     * Update an url record based on the uid and domain with the new page, language, parameters and type
     *
     * @param int   $uid
     * @param int   $page
     * @param int   $language
     * @param array $parameters
     * @param int   $type
     */
    private function updateUrl($uid, $page, $language, $parameters, $type = self::URI_TYPE_NORMAL)
    {
        $parameters = \Nawork\NaworkUri\Utility\GeneralUtility::implode_parameters($parameters, false);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableConfiguration->getUrlTable());
        $queryBuilder->update($this->tableConfiguration->getUrlTable())
            ->set('page_uid', $queryBuilder->createNamedParameter($page, \PDO::PARAM_INT), false)
            ->set('sys_language_uid', $queryBuilder->createNamedParameter($language, \PDO::PARAM_INT), false)
            ->set('parameters', $queryBuilder->createNamedParameter($parameters, \PDO::PARAM_STR), false)
            ->set('parameters_hash', $queryBuilder->createNamedParameter(md5($parameters), \PDO::PARAM_STR), false)
            ->set('type', $queryBuilder->createNamedParameter($type, \PDO::PARAM_INT), false)
            ->set('tstamp', $queryBuilder->createNamedParameter(time(), \PDO::PARAM_INT), false)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)))
            ->execute();
    }

    /**
     * Creates old url for the given page,language and paramters, the should not but might be more than one
     *
     * @param int         $domain
     * @param int         $pageId
     * @param int         $language
     * @param array       $parameters
     * @param int|boolean $excludeUid
     */
    private function makeOldUrl($domain, $pageId, $language, $parameters, $excludeUid = false)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableConfiguration->getUrlTable());
        $queryBuilder->update($this->tableConfiguration->getUrlTable())
            ->set('type', $queryBuilder->createNamedParameter(self::URI_TYPE_OLD, \PDO::PARAM_INT), false)
            ->set('tstamp', $queryBuilder->createNamedParameter(time(), \PDO::PARAM_INT), false)
            ->where(
                $queryBuilder->expr()->eq('parameters_hash', $queryBuilder->createNamedParameter(md5(\Nawork\NaworkUri\Utility\GeneralUtility::implode_parameters($parameters, false)), \PDO::PARAM_STR)),
                $queryBuilder->expr()->eq('domain', $queryBuilder->createNamedParameter($domain, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('page_uid', $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($language, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter(self::URI_TYPE_NORMAL, \PDO::PARAM_INT))
            );
        if ($excludeUid !== false) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->neq('uid', $queryBuilder->createNamedParameter($excludeUid, \PDO::PARAM_INT))
            );
        }
        $queryBuilder->execute();
    }

    /**
     * Make sure this URI is unique for the current domain
     *
     * @param int    $pageUid
     * @param int    $language
     * @param string $path
     * @param array  $parameters
     * @param int    $domain
     *
     * @return string unique URI
     * @throws \Doctrine\DBAL\DBALException
     */
    public function unique($pageUid, $language, $path, $parameters, $domain)
    {
        $parameterHash = md5(\Nawork\NaworkUri\Utility\GeneralUtility::implode_parameters($parameters, false));
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableConfiguration->getUrlTable());
        $queryBuilder->count('uid')->from($this->tableConfiguration->getUrlTable())->where(
            $queryBuilder->expr()->orX(
                $queryBuilder->expr()->neq('page_uid', $queryBuilder->createNamedParameter($pageUid, \PDO::PARAM_INT)),
                $queryBuilder->expr()->neq('sys_language_uid', $queryBuilder->createNamedParameter($language, \PDO::PARAM_INT)),
                $queryBuilder->expr()->neq('parameters_hash', $queryBuilder->createNamedParameter($parameterHash, \PDO::PARAM_STR))
            ),
            $queryBuilder->expr()->eq('path_hash', $queryBuilder->createNamedParameter(md5($path), \PDO::PARAM_STR)),
            $queryBuilder->expr()->eq('domain', $queryBuilder->createNamedParameter($domain, \PDO::PARAM_INT))
        )->setMaxResults(1);
        $statement = $queryBuilder->execute();
        if ($statement->fetchColumn(0) > 0) {
            /* so we have to make the url unique */
            $queryBuilder->resetQueryParts();
            $queryBuilder->select('path')->from($this->tableConfiguration->getUrlTable())->where(
                $queryBuilder->expr()->eq('page_uid', $queryBuilder->createNamedParameter($pageUid, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($language, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter(self::URI_TYPE_NORMAL, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('parameters_hash', $queryBuilder->createNamedParameter($parameterHash, \PDO::PARAM_STR)),
                $queryBuilder->expr()->eq('domain', $queryBuilder->createNamedParameter($domain, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('original_path', $queryBuilder->createNamedParameter($path, \PDO::PARAM_STR))
            )->setMaxResults(1);
            $statement = $queryBuilder->execute();
            if ($statement->rowCount() === 1) {
                /* there is a url found with the parameter set, so lets use this path */
                $cachedUrl = $statement->fetch();

                return $cachedUrl['url'];
            }
            // make the uri unique
            $appendIteration = 0;
            $appendValue = ConfigurationUtility::getConfiguration()->getGeneralConfiguration()->getAppend();
            $baseUri = substr($path, -(strlen($appendValue))) == $appendValue ? substr($path, 0, -strlen($appendValue)) : $path;
            // Prepare the statement
            $queryBuilder->resetQueryParts();
            $queryBuilder->count('uid')->from($this->tableConfiguration->getUrlTable())->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->neq('page_uid', $queryBuilder->createNamedParameter($pageUid, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->neq('sys_language_uid', $queryBuilder->createNamedParameter($language, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->neq('parameters_hash', $queryBuilder->createNamedParameter($parameterHash, \PDO::PARAM_STR))
                ),
                $queryBuilder->expr()->eq('domain', $queryBuilder->createNamedParameter($domain, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('path_hash', $queryBuilder->createNamedParameter('', \PDO::PARAM_STR, ':pathHash'))
            );
            $preparedStatementSql = $queryBuilder->getSQL();
            $preparedParameters = $queryBuilder->getParameters();
            do {
                ++$appendIteration;
                if ($appendIteration > 10) {
                    return false; // return false, to throw an exception in writeUrl function
                }
                if (!empty($baseUri)) {
                    $tmp_uri = $baseUri . '-' . $appendIteration . $appendValue; // add the unique part and the uri append part to the base uri
                } else {
                    $tmp_uri = $appendIteration . $appendValue;
                }
                $preparedParameters['pathHash'] = md5($tmp_uri);
                $statement = $queryBuilder->getConnection()->executeQuery($preparedStatementSql, $preparedParameters);
            } while ($statement->fetchColumn(0) > 0);

            return $tmp_uri;
        }

        return $path;
    }

}
