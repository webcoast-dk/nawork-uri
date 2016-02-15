<?php

namespace Nawork\NaworkUri\ViewHelpers\Link;


use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class ModuleViewHelper extends AbstractTagBasedViewHelper
{
    protected $tagName = 'a';

    /**
     * @param string $module
     * @param string $mode
     * @param string $table
     * @param int    $recordUid
     * @param int    $pid
     * @param array  $defaults
     * @param string $overrideReturnUrl
      *
     * @return string
     */
    public function render($module, $mode, $table, $recordUid = NULL, $pid = 0, $defaults = array(), $overrideReturnUrl = NULL)
    {
        $parameters['returnUrl'] = $overrideReturnUrl !== NULL ? $overrideReturnUrl : GeneralUtility::getIndpEnv('REQUEST_URI');
        if ($mode === 'edit') {
            $parameters['edit'][$table][$recordUid] = 'edit';
        }
        elseif ($mode === 'new') {
            $parameters['edit'][$table][$pid] = 'new';
            ArrayUtility::mergeRecursiveWithOverrule(
                $parameters,
                array(
                    'edit' => array(
                        $table => $defaults
                    )
                )
            );
        }
        $moduleUrl = BackendUtility::getModuleUrl($module, $parameters);

        $this->tag->addAttribute('href', $moduleUrl, FALSE);
        $this->tag->setContent($this->renderChildren());

        return $this->tag->render();
    }
}
