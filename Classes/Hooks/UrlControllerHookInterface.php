<?php

namespace Nawork\NaworkUri\Hooks;

use TYPO3\CMS\Core\SingletonInterface;

interface UrlControllerHookInterface extends SingletonInterface
{
    public function params2uri_linkDataPreProcess(&$linkData, &$typoScriptFrontendController);
}
