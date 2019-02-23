<?php

namespace Nawork\NaworkUri\Utility;

/*
 * Helper functions
 */

use Doctrine\DBAL\Query\QueryBuilder;
use Nawork\NaworkUri\Configuration\TableConfiguration;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class GeneralUtility
{
    /**
     * Cache determined domain name to uid mapping in here to avoid
     * one or more database queries per generated link
     *
     * @var array
     */
    private static $domainNameUidMapping = [];

    /**
     * Tests if the input can be interpreted as integer.
     *
     * !!!this is a direct copy of the according method from typo3 4.6!!!
     *
     * @param $var mixed Any input variable to test
     *
     * @return boolean Returns TRUE if string is an integer
     */
    public static function canBeInterpretedAsInteger($var)
    {
        if ($var === '') {
            return false;
        }

        return (string)intval($var) === (string)$var;
    }

    /**
     * Explode URI Parameters
     *
     * @param string $param_string Parameter Part of URI
     *
     * @return array Exploded Parameters
     */
    public static function explode_parameters($param_string)
    {
        if (empty($param_string)) {
            return [];
        }

        $param_string = rawurldecode(urldecode(html_entity_decode($param_string)));

        $result = [];
        $tmp = explode('&', $param_string);
        foreach ($tmp as $part) {
            list($key, $value) = explode('=', $part);
            if (substr($key, -2) == '[]') {
                /* we have an array value */
                if (!array_key_exists($key, $result)) {
                    $result[$key] = [];
                }
                $result[$key][] = $value;
            } else {
                $result[$key] = $value;
            }
        }
        krsort($result);

        return $result;
    }

    /**
     * Implode URI Parameters
     *
     * @param array   $params_array Parameter Array
     * @param boolean $encode       Return the parameters url encoded or not, default is yes
     *
     * @return string Imploded Parameters
     */
    public static function implode_parameters($params_array, $encode = true)
    {
        self::arrayKsortRecursive($params_array);
        $queryStringParts = [];
        foreach ($params_array as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    $queryStringParts[] = ($encode ? rawurlencode($name) : $name) . '=' . ($encode ? rawurlencode($v) : $v);
                }
            } else {
                $queryStringParts[] = ($encode ? rawurlencode($name) : $name) . '=' . ($encode ? rawurlencode($value) : $value);
            }
        }

        if (empty($queryStringParts)) {
            return '';
        }

        return implode('&', $queryStringParts);
    }

    private static function arrayKsortRecursive(&$array)
    {
        uksort($array, [self::class, 'compareArrayKeys']);
        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value)) {
                self::arrayKsortRecursive($value);
                $array[$key] = $value;
            }
        }
    }

    private static function compareArrayKeys($a, $b)
    {
        $a = strtolower($a);
        $b = strtolower($b);

        return strcmp($a, $b);
    }

    /**
     * Sanitize the Path
     *
     * @param string $uri
     *
     * @return string
     */
    public static function sanitize_uri($uri)
    {
        $locale = self::getLocale();
        /* settings locales as in tsfe */
        if (strpos(strtolower($locale), 'tr') === false) {
            setlocale(LC_CTYPE, $locale);
        }
        setlocale(LC_COLLATE, $locale);
        setlocale(LC_MONETARY, $locale);
        setlocale(LC_TIME, $locale);

        $uri = self::uriTransliterate($uri);
        $uri = strip_tags($uri);
        $uri = strtolower($uri);
        $uri = self::uri_handle_punctuation($uri);
        $uri = self::uri_handle_whitespace($uri);
        $uri = self::uri_limit_allowed_chars($uri);
        $uri = self::uri_make_wellformed($uri);

        return $uri;
    }

    public static function uriTransliterate($uri)
    {
        foreach (ConfigurationUtility::getConfiguration()->getTransliterationsConfiguration()->getCharacters() as $from => $to) {
            $uri = str_replace($from, $to, $uri);
        }
        $uri = iconv('UTF-8', 'ASCII//TRANSLIT', $uri);

        return $uri;
    }

    /**
     * Remove whitespace characters from uri
     *
     * @param string $uri
     *
     * @return string
     */
    public static function uri_handle_whitespace($uri)
    {
        $uri = preg_replace('/[\s\-]+/u', '-', $uri);

        return $uri;
    }

    /**
     * Convert punctuation chars to -
     *  ! " # $ & ' ( ) * + , : ; < = > ? @ [ \ ] ^ ` { | } <-- Old
     *
     *    " #   & '               <   > ? @ [ \ ] ^ ` { | } %   < -- New
     *
     * @param string $uri
     *
     * @return string
     */
    public static function uri_handle_punctuation($uri)
    {
        $uri = preg_replace('/[\!\"\#\&\'\?\@\[\\\\\]\^\`\{\|\}\%\<\>\+]+/u', '-', $uri);

        return $uri;
    }

    /**
     * remove not allowed chars from uri
     * allowed chars A-Za-z0-9 - _ . ~ ! ( ) * + , : ; =
     *
     * @param string $uri
     *
     * @return string
     */
    public static function uri_limit_allowed_chars($uri)
    {
        return preg_replace('/[^A-Za-z0-9\/\-\_\.\~\!\(\)\*\:\;\=]+/u', '', $uri);
    }

    /**
     * Remove some ugly uri-formatings:
     * - slashes from the Start
     * - double slashes
     * - -/ /-
     *
     * @param string $uri
     *
     * @return string
     */
    public static function uri_make_wellformed($uri)
    {
        $uri = preg_replace('/[\-]+/', '-', $uri);
        $uri = preg_replace('/\/-/', '/', $uri);
        $uri = preg_replace('/-\//', '/', $uri);
        $uri = preg_replace('/[\/]+/', '/', $uri);
        $uri = preg_replace('/\-+/', '-', $uri);
        $uri = preg_replace('/^[\/]+/u', '', $uri);
        $uri = preg_replace('/\-$/', '', $uri);
        $uri = preg_replace('/\/$/', '', $uri);

        return $uri;
    }

    public static function isActiveBeUserSession()
    {
        if ($GLOBALS['TSFE'] instanceof TypoScriptFrontendController) {
            return $GLOBALS['TSFE']->isBackendUserLoggedIn();
        } elseif ($GLOBALS['BE_USER'] instanceof BackendUserAuthentication) {
            return $GLOBALS['BE_USER']->isExistingSessionRecord($GLOBALS['BE_USER']->id);
        }

        return false;
    }

    public static function getCurrentDomain($linkDomain = null)
    {
        $domainName = $linkDomain === null ? \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_HOST') : $linkDomain;
        if (isset(self::$domainNameUidMapping[$domainName])) {
            return self::$domainNameUidMapping[$domainName];
        }
        /* @var $tableConfiguration \Nawork\NaworkUri\Configuration\TableConfiguration */
        $tableConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TableConfiguration::class);
        $domainUid = 0;
        $queryBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableConfiguration->getDomainTable());
        $queryBuilder->select('uid', 'tx_naworkuri_masterDomain')->from($tableConfiguration->getDomainTable())->where(
            $queryBuilder->expr()->eq('domainName', $queryBuilder->createNamedParameter($domainName, \PDO::PARAM_STR)),
            $queryBuilder->expr()->eq('hidden', 0)
        )->setMaxResults(1)->getRestrictions()->removeAll();
        $domainStatement = $queryBuilder->execute();
        if ($domain = $domainStatement->fetch()) {
            $domainUid = $domain['uid'];
            if (intval($domain['tx_naworkuri_masterDomain']) > 0) {
                $domainUid = intval($domain['tx_naworkuri_masterDomain']);
                $continue = true;
                do {
                    $queryBuilder->resetQueryPart('where');
                    $queryBuilder->where(
                        $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($domainUid, \PDO::PARAM_INT))
                    );
                    $domainStatement = $queryBuilder->execute();
                    if ($domain = $domainStatement->fetch()) {
                        if (intval($domain['tx_naworkuri_masterDomain']) > 0) {
                            $domainUid = intval($domain['tx_naworkuri_masterDomain']);
                        } else {
                            $domainUid = $domain['uid'];
                            $continue = false;
                        }
                    }
                } while ($continue);
            }
        }
        self::$domainNameUidMapping[$domainName] = $domainUid;

        return $domainUid;
    }

    public static function getCurrentDomainName()
    {
        /* @var $tableConfiguration \Nawork\NaworkUri\Configuration\TableConfiguration */
        $tableConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TableConfiguration::class);
        $domainUid = self::getCurrentDomain();
        if ($domainUid > 0) {
            $queryBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableConfiguration->getDomainTable());
            $queryBuilder->select('domainName')->from($tableConfiguration->getDomainTable())->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($domainUid, \PDO::PARAM_INT))
            )->setMaxResults(1)->getRestrictions()->removeAll();
            $domainRecord = $queryBuilder->execute()->fetch();
            if (is_array($domainRecord)) {
                return $domainRecord['domainName'];
            }
        }
        throw new \Exception('Could not find a domain name for uid "' . $domainUid . '"', 1394133428);
    }

    public static function getDomainConfigurationIdentifier($domainUid)
    {
        /* @var $tableConfiguration \Nawork\NaworkUri\Configuration\TableConfiguration */
        $tableConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TableConfiguration::class);
        $queryBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableConfiguration->getDomainTable());
        $queryBuilder->select('tx_naworkuri_use_configuration')->from($tableConfiguration->getDomainTable())->where(
            $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($domainUid, \PDO::PARAM_INT))
        )->setMaxResults(1)->getRestrictions()->removeAll();
        $domainRecord = $queryBuilder->execute()->fetch();
        if (is_array($domainRecord)) {
            return $domainRecord['tx_naworkuri_use_configuration'];
        }
    }

    /**
     *
     * @param string $url The original url
     *
     * @return string The finalized url
     */
    public static function finalizeUrl($url)
    {
        // if the url already has a protocol, no more work needs to be done
        if (preg_match('/^https?:\/\//', $url)) {
            return $url;
        }

        // cut of beginning "/"
        if (substr($url, 0, 1) === '/') {
            $url = substr($url, 1);
        }

        $prefix = '/';
        if (!empty($GLOBALS['TSFE']->absRefPrefix)) {
            $prefix = $GLOBALS['TSFE']->absRefPrefix;
        }

        return $prefix . $url;
    }

    public static function sendRedirect($url, $status)
    {
        header('X-Redirect-By: nawork_uri');
        HttpUtility::redirect($url, $status);
    }

    public static function getLocale()
    {
        $locale = $GLOBALS['TSFE']->config['config']['locale_all'];
        if (empty($locale)) {
            $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nawork_uri']);
            $locale = $extConf['default_locale'];
        }
        if (empty($locale)) {
            $locale = 'en_US';
        }

        return $locale;
    }

    /**
     * This function filters the given parameters if there exists a configuration for it.
     *
     * @param array $parameters
     *
     * @return array
     */
    public static function filterConfiguredParameters($parameters)
    {
        $encodableParameters = [];
        $parameterNames = array_keys($parameters);
        // check parameter configurations, which parameters can be encoded
        foreach (ConfigurationUtility::getConfiguration()->getParametersConfiguration()->getParameterTransformationConfigurations() as $parameterConfiguration) {
            if (in_array($parameterConfiguration->getName(), $parameterNames)) {
                $encodableParameters[$parameterConfiguration->getName()] = $parameters[$parameterConfiguration->getName()];
            }
        }

        ksort($encodableParameters);
        $unencodableParameters = array_diff_key($parameters, $encodableParameters);

        return [$encodableParameters, $unencodableParameters];
    }

    public static function aliasToId($alias)
    {
        if (self::canBeInterpretedAsInteger($alias)) {
            return $alias;
        }
        /* @var $tableConfiguration \Nawork\NaworkUri\Configuration\TableConfiguration */
        $tableConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TableConfiguration::class);
        $queryBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableConfiguration->getPageTable());
        $queryBuilder->select('uid')->from($tableConfiguration->getPageTable())->where(
            $queryBuilder->expr()->eq('alias', $queryBuilder->createNamedParameter($alias, \PDO::PARAM_STR)),
            $queryBuilder->expr()->eq('deleted', 0),
            $queryBuilder->expr()->eq('hidden', 0)
        )->setMaxResults(1)->getRestrictions()->removeAll();
        $statement = $queryBuilder->execute();
        if ($statement->rowCount() === 1) {
            return $statement->fetch()['uid'];
        }

        return $alias;
    }

    public static function log($msg, $severity, $data = [])
    {
        $logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        $logger->log($severity, vsprintf($msg, $data));
    }
}
