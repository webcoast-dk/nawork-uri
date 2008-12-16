<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Jan Bednarik <info@bednarik.org>
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


	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');

$LANG->includeLLFile('EXT:cooluri/mod1/locallang.xml');
require_once(PATH_t3lib.'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]

require_once '../class.tx_cooluri.php';

/**
 * Module 'CoolURI' for the 'cooluri' extension.
 *
 * @author	Jan Bednarik <info@bednarik.org>
 * @package	TYPO3
 * @subpackage	tx_cooluri
 */
class  tx_cooluri_module1 extends t3lib_SCbase {
				var $pageinfo;

				/**
				 * Initializes the Module
				 * @return	void
				 */
				function init()	{
					global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

					parent::init();

					/*
					if (t3lib_div::_GP('clear_all_cache'))	{
						$this->include_once[] = PATH_t3lib.'class.t3lib_tcemain.php';
					}
					*/
				}

				/**
				 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
				 *
				 * @return	void
				 */
				function menuConfig()	{
					global $LANG;
					$this->MOD_MENU = Array (
						'function' => Array (
							'1' => $LANG->getLL('function1'),
							'2' => $LANG->getLL('function2'),
							'3' => $LANG->getLL('function3'),
						)
					);
					parent::menuConfig();
				}

				/**
				 * Main function of the module. Write the content to $this->content
				 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
				 *
				 * @return	[type]		...
				 */
				function main()	{
					global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

					// Access check!
					// The page will show only if there is a valid page and if this page may be viewed by the user
					$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
					$access = is_array($this->pageinfo) ? 1 : 0;

					if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))	{
            
            $this->doc = t3lib_div::makeInstance('noDoc');
						$this->doc->backPath = $BACK_PATH;
            
            $this->doc->JScode = '
				<link rel="stylesheet" type="text/css" href="style.css" />
			'; 
            
            $this->content.=$this->doc->startPage($LANG->getLL('title'));
						$this->content.=$this->doc->header('CoolURIs\' project\'s LinkManager');
            
            require_once '../cooluri/manager/linkmanager.Main.php';
            /*if (file_exists(PATH_typo3conf.'CoolUriConf.xml'))
              $lm = new LinkManger_Main('index.php',PATH_typo3conf.'CoolUriConf.xml');
            elseif (file_exists('../cooluri/CoolUriConf.xml'))
              $lm = new LinkManger_Main('index.php','../cooluri/CoolUriConf.xml');
            else {
              $this->content .= 'XML Config file not found';
              return;
            }*/
            
            $this->confArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['cooluri']);
            if (file_exists($BACK_PATH.'../'.$this->confArray['XMLPATH'].'CoolUriConf.xml'))
              $lt = $BACK_PATH.'../'.$this->confArray['XMLPATH'].'CoolUriConf.xml';
            elseif (file_exists(PATH_typo3conf.'CoolUriConf.xml'))
              $lt = PATH_typo3conf.'CoolUriConf.xml';
            elseif (file_exists(dirname(__FILE__).'/../cooluri/CoolUriConf.xml'))
              $lt = dirname(__FILE__).'/cooluri/CoolUriConf.xml';
            else {
              $this->content .= 'XML Config file not found';
              return;
            }
            $lm = new LinkManger_Main('index.php',$lt);
              
            
            $this->content .= $lm->menu();
            $this->content .= $lm->main();
            
					} else {
							// If no access or if ID == zero

						$this->doc = t3lib_div::makeInstance('mediumDoc');
						$this->doc->backPath = $BACK_PATH;

						$this->content.=$this->doc->startPage($LANG->getLL('title'));
						$this->content.=$this->doc->header($LANG->getLL('title'));
						$this->content.=$this->doc->spacer(5);
						$this->content.=$this->doc->spacer(10);
					}
				}

				/**
				 * Prints out the module HTML
				 *
				 * @return	void
				 */
				function printContent()	{
					$this->content .= '</div>';
          echo $this->content;
				}


			}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cooluri/mod1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cooluri/mod1/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_cooluri_module1');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>
