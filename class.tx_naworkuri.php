<?php

require_once 'lib/class.tx_naworkuri_transformer.php';

class tx_naworkuri {

    /**
     * decode uri and extract parameters
     *
     * @param unknown_type $params
     * @param unknown_type $ref
     */
    function uri2params($params, $ref) {
        global $TYPO3_CONF_VARS;


        if (
                $params['pObj']->siteScript
                && substr($params['pObj']->siteScript, 0, 9) != 'index.php'
                && substr($params['pObj']->siteScript, 0, 1) != '?'
        ) {

            $uri = $params['pObj']->siteScript;

            // translate uri
            $extConf = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['nawork_uri']);
            /* @var $configReader tx_naworkuri_configReader */
            $configReader = t3lib_div::makeInstance('tx_naworkuri_configReader', $extConf['XMLPATH']);
            $translator = t3lib_div::makeInstance('tx_naworkuri_transformer', $configReader, $extConf['MULTIDOMAIN']);
            $uri_params = $translator->uri2params($uri);

            if ($uri_params) { // uri found
                $params['pObj']->id = $uri_params['id'];
                unset($uri_params['id']);
                $params['pObj']->mergingWithGetVars($uri_params);
            } else { // handle 404
                if ($configReader->hasPageNotFoundConfig()) {
                    header('Content-Type: text/html; charset=utf-8');
                    header($configReader->getPageNotFoundConfigStatus());
                    switch ($configReader->getPageNotFoundConfigBehaviorType()) {
                        case 'message':
                            $res = $configReader->getPageNotFoundConfigBehaviorValue();
                            break;
                        case 'page':
                            if (t3lib_div::getIndpEnv('HTTP_USER_AGENT') != 'nawork_uri') {
                                $curl = curl_init();
                                curl_setopt($curl, CURLOPT_URL, $configReader->getPageNotFoundConfigBehaviorValue());
                                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
                                curl_setopt($curl, CURLOPT_TIMEOUT, 30);
                                curl_setopt($curl, CURLOPT_USERAGENT, 'nawork_uri');
                                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                                $res = curl_exec($curl);
                            } else {
                                $res = '404 not found! The 404 Page URL ' . $configReader->getPageNotFoundConfigBehaviorValue() . ' seems to cause a redirect loop.';
                            }
                            break;
                        case 'redirect':
                            $path = html_entity_decode($configReader->getPageNotFoundConfigBehaviorValue());
                            if (!($_SERVER['REQUEST_METHOD'] == 'POST' && preg_match('/index.php/', $_SERVER['SCRIPT_NAME']))) {
                                header('Location: ' . $path, true, 301);
                                exit;
                            }
                        default:
                            $res = '';
                    }
                    echo $res;
                    exit;
                }
            }
        }
    }

    /**
     * convert typolink parameters 2 uri
     *
     * @param array $params
     * @param array $ref
     */
    function params2uri(&$link, $ref) {
        global $TYPO3_CONF_VARS;

        if (
                $GLOBALS['TSFE']->config['config']['tx_naworkuri.']['enable'] == 1
                && $link['LD']['url']
        ) {
            list($path, $params) = explode('?', $link['LD']['totalURL']);
            $extConf = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['nawork_uri']);
            $configReader = t3lib_div::makeInstance('tx_naworkuri_configReader', $extConf['XMLPATH']);
            $translator = t3lib_div::makeInstance('tx_naworkuri_transformer', $configReader, (boolean)$extConf['MULTIDOMAIN']);
            $link['LD']['totalURL'] = $GLOBALS['TSFE']->config['config']['absRefPrefix'] . $translator->params2uri($params);
        }
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $params
     * @param unknown_type $ref
     */
    function redirect2uri($params, $ref) {
        global $TYPO3_CONF_VARS;
        if (
                $GLOBALS['TSFE']->config['config']['tx_naworkuri.']['enable'] == 1
                && empty($_GET['ADMCMD_prev'])
                && $GLOBALS['TSFE']->config['config']['tx_naworkuri.']['redirect'] == 1
                && $GLOBALS['TSFE']->siteScript
                && (substr($GLOBALS['TSFE']->siteScript, 0, 9) == 'index.php'
                || substr($GLOBALS['TSFE']->siteScript, 0, 1) == '?')
        ) {
            list($path, $params) = explode('?', $GLOBALS['TSFE']->siteScript);
            $extConf = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['nawork_uri']);
            $configReader = t3lib_div::makeInstance('tx_naworkuri_configReader', $extConf['XMLPATH']);
            $translator = t3lib_div::makeInstance('tx_naworkuri_transformer', $configReader);

			$dontCreateNewUrls = true;
			$tempParams = tx_naworkuri_helper::explode_parameters($params);
			if((count($tempParams) < 3 && array_key_exists('L', $tempParams) && array_key_exists('id', $tempParams)) || (count($tempParams) < 2 && array_key_exists('id', $tempParams))) {

			}
            $uri = $translator->params2uri($params, $dontCreateNewUrls);
            if (!($_SERVER['REQUEST_METHOD'] == 'POST') && ($path == 'index.php' || $path == '') && $uri !== false) {
                header('Location: ' . $GLOBALS['TSFE']->config['config']['baseURL'] . $uri, true, 301);
                exit;
            }
        }
    }

    /**
     * Update the md5 values automatically
     *
     * @param unknown_type $incomingFieldArray
     * @param unknown_type $table
     * @param unknown_type $id
     * @param unknown_type $res
     */
    public function processDatamap_preProcessFieldArray(&$incomingFieldArray, &$table, &$id, &$res) {
        if ($table == "tx_naworkuri_uri") {
            if ($incomingFieldArray['path'] || $incomingFieldArray['path'] == '')
                $incomingFieldArray['hash_path'] = md5($incomingFieldArray['path']);
            if ($incomingFieldArray['params'] || $incomingFieldArray['params'] == '')
                $incomingFieldArray['hash_params'] = md5($incomingFieldArray['params']);
        }
    }

}

?>
