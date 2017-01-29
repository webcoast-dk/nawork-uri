<?php

namespace Nawork\NaworkUri\Signals;


use Nawork\NaworkUri\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\SingletonInterface;

class AfterSetting404PageId implements SingletonInterface {

    /**
     * @param array $params
     * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $frontendController
     */
    public function afterSetting404PageId($params, $frontendController) {
        $configuration = ConfigurationUtility::getConfiguration();
        $parameterConfiguration = $configuration->getParametersConfiguration();
        $languageConfiguration = $parameterConfiguration->getParameterTransformationConfigurationByName("L");
        $url = '/' . $params["currentUrl"];

        if ($languageConfiguration->getType() === 'ValueMap') {
            foreach ($languageConfiguration->getMappings() as $sys_language_uid => $languageValue) {
                if(preg_match('/\/' . $languageValue['default'] . '\//', $url)) {
                    //@TODO Replace!
                    header('Location: /index.php?id=' . $frontendController->id . '&L=' . $sys_language_uid);
                }
            }
        }
    }
}
