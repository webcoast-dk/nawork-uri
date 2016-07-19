<?php

namespace Nawork\NaworkUri\Utility;


use TYPO3\CMS\Core\Utility\GeneralUtility;

class DebugUtility
{
    public static function debug($title, $data, $tag, $additionalEnvironment = null)
    {
        $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nawork_uri']);
        if ($extensionConfiguration['debugEnable']) {
            if (empty($extensionConfiguration['debugEnableTags']) || $extensionConfiguration['debugEnableTags'] === '*' || in_array(
                    $tag,
                    GeneralUtility::trimExplode(',', $extensionConfiguration['debugEnableTags'])
                )
            ) {
                $logFile = $extensionConfiguration['debugLogFile'];
                if (!file_exists($logFile)) {
                    touch($logFile);
                }
                $temporaryServer = $_SERVER;
                unset($temporaryServer['HTTP_ACCEPT']);
                unset($temporaryServer['HTTP_ACCEPT_LANGUAGE']);
                unset($temporaryServer['HTTP_ACCEPT_ENCODING']);
                unset($temporaryServer['HTTP_COOKIE']);
                unset($temporaryServer['HTTP_CONNECTION']);
                unset($temporaryServer['PATH']);
                unset($temporaryServer['SERVER_SIGNATURE']);
                unset($temporaryServer['SERVER_SOFTWARE']);
                unset($temporaryServer['SERVER_NAME']);
                unset($temporaryServer['SERVER_ADDR']);
                unset($temporaryServer['SERVER_PORT']);
                unset($temporaryServer['DOCUMENT_ROOT']);
                unset($temporaryServer['CONTEXT_PREFIX']);
                unset($temporaryServer['CONTEXT_DOCUMENT_ROOT']);
                unset($temporaryServer['SERVER_ADMIN']);
                unset($temporaryServer['REMOTE_PORT']);
                unset($temporaryServer['GATEWAY_INTERFACE']);
                $environment = array(
                    '_SERVER' => $temporaryServer,
                    'hostname' => gethostname()
                );
                if ($additionalEnvironment !== null) {
                    $environment['additionalEnvironment'] = $additionalEnvironment;
                }
                $identifier = crc32(serialize($_SERVER));
                $logData = array(
                    'environment' => $environment,
                    'identifier' => $identifier,
                    'time' => microtime(true),
                    'title' => $title,
                    'data' => $data,
                    'tag' => $tag
                );
                file_put_contents($logFile, json_encode($logData) . PHP_EOL, FILE_APPEND);
            }
        }
    }
}
