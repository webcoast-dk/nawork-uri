<?php

namespace Nawork\NaworkUri\Backend\Template\Components\Menu;



use TYPO3\CMS\Backend\Template\Components\Menu\MenuItem;

class Menu extends \TYPO3\CMS\Backend\Template\Components\Menu\Menu
{
    protected $renderAsCheckbox = false;

    /**
     * @return boolean
     */
    public function isRenderAsCheckbox()
    {
        return $this->renderAsCheckbox;
    }

    /**
     * @param boolean $renderAsCheckbox
     */
    public function setRenderAsCheckbox($renderAsCheckbox)
    {
        $this->renderAsCheckbox = $renderAsCheckbox;
    }

    public function addMenuItem(MenuItem $menuItem)
    {
        $this->menuItems[] = clone $menuItem;
    }
}
