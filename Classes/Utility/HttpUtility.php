<?php

namespace Nawork\NaworkUri\Utility;


use Nawork\NaworkUri\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class HttpUtility
{
    /**
     * @param string $url The url to fetch
     *
     * @return string The content of the fetched url
     */
    public static function getUrlByCurl($url)
    {
        /** @var ExtensionConfiguration $extensionConfiguration */
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $maxRedirects = 5;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_USERAGENT, 'nawork_uri');
        curl_setopt($curl, CURLOPT_REFERER, GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
        // disable check for valid peer certificate: this should not be used in
        // production environments for security reasons
        if ($extensionConfiguration->getNoSslVerify()) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        }
        if (version_compare(phpversion(), '5.6.0', '>=') || strcasecmp(ini_get('open_basedir'), '') === 0) {
            // if php version of greater or equal than 5.6.0 or "open_basedir" is not set, return the normal curl_exec
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($curl, CURLOPT_MAXREDIRS, $maxRedirects);

            return curl_exec($curl);
        } else {
            // otherwise use workaround to follow redirects
            return self::curl_exec_open_basedir($curl, $maxRedirects);
        }
    }

    protected static function curl_exec_open_basedir($curl, $maxRedirects)
    {
        $redirectsLeft = $maxRedirects;
        do {
            $output = curl_exec($curl);
            $info = curl_getinfo($curl);
            if ($info['http_code'] > 300 && $info['http_code'] < 400 && $info['redirect_url'] !== '') {
                curl_setopt($curl, CURLOPT_URL, $info['redirect_url']);
                --$redirectsLeft;
            } else {
                $redirectsLeft = 0;
            }
        } while ($redirectsLeft > 0);

        return $output;
    }
}