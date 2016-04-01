<?php

namespace Nawork\NaworkUri\Controller;


use Nawork\NaworkUri\Utility\ConfigurationUtility;

class ConfigurationController extends AbstractController
{
    public function indexAction()
    {
        $this->view->assign('registeredConfigurations', array_keys($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nawork_uri']['Configurations']));
    }

    /**
     * The name of the configuration to show
     *
     * @param string $name
     */
    public function showAction($name)
    {
       $this->view->assign('configuration', ConfigurationUtility::getConfiguration($name));
    }
}
