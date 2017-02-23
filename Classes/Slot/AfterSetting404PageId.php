<?php

namespace Nawork\NaworkUri\Slot;

use Nawork\NaworkUri\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\SingletonInterface;

class AfterSetting404PageId implements SingletonInterface {

    /**
     * @param array $params
     */
    public function afterSetting404PageId($params) {
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
