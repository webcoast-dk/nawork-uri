<?php

namespace Nawork\NaworkUri\ViewHelpers\Uri;


use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

class ModuleViewHelper extends AbstractViewHelper
{
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
        if (VersionNumberUtility::convertVersionNumberToInteger(VersionNumberUtility::getCurrentTypo3Version()) < 7000000) {
            switch ($module) {
                case 'record_edit':
                    $moduleUrl = $GLOBALS['BACK_PATH'].'alt_doc.php?' . trim(GeneralUtility::implodeArrayForUrl('', $parameters, '', TRUE, TRUE), '&');
                    break;
            }
        } else {
            $moduleUrl = BackendUtility::getModuleUrl($module, $parameters);
        }

        return $moduleUrl;
    }
}
