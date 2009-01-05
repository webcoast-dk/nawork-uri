<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009  <>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


require_once(PATH_t3lib.'class.t3lib_extobjbase.php');



/**
 * Module extension (addition to function menu) 'n@work URI Management' for the 'nawork_uri2' extension.
 *
 * @author	 <>
 * @package	TYPO3
 * @subpackage	tx_naworkuri2
 */
class tx_naworkuri_modfunc_info extends t3lib_extobjbase {

					/**
					 * Returns the module menu
					 *
					 * @return	Array with menuitems
					 */
					function modMenu()	{
						global $LANG;

						return array (
							'pages' => array (
								0 => $LANG->getLL('pages_0'),
								2 => $LANG->getLL('pages_2'),
								1 => $LANG->getLL('pages_1')
							),
							'stat_type' => array(
								0 => $LANG->getLL('stat_type_0'),
								1 => $LANG->getLL('stat_type_1'),
								2 => $LANG->getLL('stat_type_2'),
							),
							'depth' => array(
								0 => $LANG->getLL('depth_0'),
								1 => $LANG->getLL('depth_1'),
								2 => $LANG->getLL('depth_2'),
								3 => $LANG->getLL('depth_3')
							)
						);
					}

					/**
					 * Main method of the module
					 *
					 * @return	HTML
					 */
					function main()	{
							// Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
						global $SOBE,$BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

						$menu=array();
						$menu[]= t3lib_BEfunc::getFuncMenu($this->pObj->id,'SET[depth]',$this->pObj->MOD_SETTINGS['depth'],$this->pObj->MOD_MENU['depth'],'index.php');
						// $menu[]= t3lib_BEfunc::getFuncMenu($this->pObj->id,'SET[depth]',$this->pObj->MOD_SETTINGS['depth'],$this->pObj->MOD_MENU['depth'],'index.php');
						
						$theOutput.=$this->pObj->doc->spacer(5);
						$theOutput.=$this->pObj->doc->section("Menu",implode(" - ",$menu),0,1);

						$theOutput.=$this->pObj->doc->spacer(5);
						$theOutput.=$this->pObj->doc->section($LANG->getLL("title"),"Dummy content here...",0,1);
												
						return $theOutput;
					}
				}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nawork_uri/modfunc_info/class.tx_naworkuri_modfunc_info.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nawork_uri/modfunc_info/class.tx_naworkuri_modfunc_info.php']);
}

?>