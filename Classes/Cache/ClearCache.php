<?php

namespace Nawork\NaworkUri\Cache;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * Description of ClearCache
 *
 * @author thorben
 */
class ClearCache
{
    public function clearUrlCache()
    {
        $response = GeneralUtility::makeInstance(Response::class);
        if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigVal('options.clearCache.urls')) {
            if (VersionNumberUtility::convertVersionNumberToInteger(VersionNumberUtility::getCurrentTypo3Version()) < VersionNumberUtility::convertVersionNumberToInteger('9.5.0')) {
                $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_naworkuri_uri', '', ['tstamp' => 0], ['tstamp']);
            } else {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_naworkuri_uri');
                $queryBuilder->update('tx_naworkuri_uri')
                    ->set('tstamp', 0)
                    ->execute();
            }
            $response->withStatus(200);
        } else {
            $response->withStatus(403);
        }

        return $response;
    }

    /**
     * Clears the configuration cache, this is needed after the configuration has been changed
     *
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     */
    public function clearConfigurationCache()
    {
        $response = GeneralUtility::makeInstance(Response::class);
        if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigValue('options.clearCacheCmd.urlConfiguration')) {
            /** @var FrontendInterface $cache */
            $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('naworkuri_configuration');
            $cache->flush();
            $response->withStatus(200);
        } else {
            $response->withStatus(403);
        }

        return $response;
    }
}
