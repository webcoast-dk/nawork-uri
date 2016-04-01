<?php

namespace Nawork\NaworkUri\ViewHelpers;


use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

class PrintrViewHelper extends AbstractViewHelper
{
    /**
     * Any object to be printed
     *
     * @param object|array $object
     *
     * @return string
     */
    public function render($object = null)
    {
        if ($object === null) {
            $object = $this->renderChildren();
        }

        $output = print_r($object, true);
        $output = htmlspecialchars($output);
        $output = str_replace(array(' ', "\t", "\n", '<br /><br />'), array('&nbsp;', '&nbsp;&nbsp;&nbsp;&nbsp;', '<br />', '<br />'), $output);

        return $output;
    }
}