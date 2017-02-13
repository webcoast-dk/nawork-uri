<?php

namespace Nawork\NaworkUri\Signals;

use Nawork\NaworkUri\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\SingletonInterface;

class BeforeInternal404Request implements SingletonInterface {

    /**
     * @param array $params
     */
    public function beforeInternal404Request($params) {
        $configuration = ConfigurationUtility::getConfiguration();
        $parameterConfiguration = $configuration->getParametersConfiguration();
        $languageConfiguration = $parameterConfiguration->getParameterTransformationConfigurationByName('L');
        $url = '/' . $params['currentUrl'];

        if ($languageConfiguration->getType() === 'ValueMap') {
            foreach ($languageConfiguration->getMappings() as $sys_language_uid => $languageValue) {
                if(preg_match('/\/' . $languageValue['default'] . '\//', $url)) {
                    $GLOBALS['TSFE']->mergingWithGetVars(['L' => $sys_language_uid]);
                }
            }
        }
    }
}
