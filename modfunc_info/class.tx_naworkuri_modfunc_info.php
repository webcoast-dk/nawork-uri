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


require_once (PATH_t3lib.'class.t3lib_extobjbase.php');
require_once (PATH_t3lib.'class.t3lib_iconworks.php');



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
			/*
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
			*/
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

		$this->back_path = 'mod/web/info/index.php?id='.$this->pObj->id.'&SET[depth]='.$this->pObj->MOD_SETTINGS['depth'];
		$menu=array();
		$menu[]= t3lib_BEfunc::getFuncMenu($this->pObj->id,'SET[depth]',$this->pObj->MOD_SETTINGS['depth'],$this->pObj->MOD_MENU['depth'],'index.php');
		
		$theOutput.=$this->pObj->doc->spacer(5);
		$theOutput.=$this->pObj->doc->section("Menu",implode(" - ",$menu),0,1);

		$theOutput.=$this->pObj->doc->spacer(5);
		$theOutput.=$this->pObj->doc->section($LANG->getLL("title"),$this->show_page_tree(),0,1);

		
		
		return $theOutput;
		
		
	}
	
	function show_page_tree(){
		$content = ''.$this->pObj->id.$this->pObj->MOD_SETTINGS['depth'];
		// show page info
		$content .= '<table><tr><th>Path</th><th>Params</th><th>action</th></tr>';
		$content .= $this->show_page($this->pObj->id ).'</table>';
		return $content;
	}
	
	function show_page($id, $depth=0 ){
		$content = '';
		
		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery( '*' , 'pages', 'uid='.(int)$id.' AND deleted=0', '', '' ,1 );
		$row   = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres);
		
			// show_page_urls
		$content  .= $this->show_page_info($row, '');
		$content  .= $this->show_page_urls($id,  '&nbsp;&nbsp;');
		
			// show subpages	
		if ($depth < $this->pObj->MOD_SETTINGS['depth']){
			$content  .= $this->show_subpages($id, $depth+1);
		}
		return $content;
	}
	
	function show_page_info($row, $indent){ 
		$iconP = '<img src="../../../'.t3lib_iconWorks::getIcon('pages',$row).'" />';
		
		return '<tr><th colspan="2"  style="text-align:left;">'.$indent.$iconP.$row['title'].'</th><td>&nbsp;<!-- delete all / update all--></td></tr>';
	}
	
	function show_page_urls($id, $indent =''){
		$result = '';
		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery( '*' , 'tx_naworkuri_uri', 'pid='.(int)$id.' AND deleted=0' );
		while ($row   = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)){
			
			$iconU = '<img src="../../../'.t3lib_iconWorks::getIcon('tx_naworkuri_uri',$row).'" />';
			$iconL = $this->getLanguageIcon($row['sys_language_uid']);
			
			$delete   = '<a onclick="if (confirm(\'foo\') ) {jumpToUrl(\'../../../tce_db.php?&cmd[tx_naworkuri_uri]['.$row['uid'].'][delete]=1&redirect='.urlencode($this->back_path).'&vC=d3ea73f1a3&prErr=1&uPT=1\');} return false;" href="#">delete</a>';
			$edit     = '<a onclick="window.location.href=\'../../../alt_doc.php?returnUrl='.urlencode($this->back_path).'&edit[tx_naworkuri_uri]['.$row['uid'].']=edit\'; return false;" href="#">edit</a>';
			
			$result .= '<tr><td>'.$indent.' '.$iconU.$iconL.' '.$row['domain'].' : '.$row['path'].'</td><td>'.$row['params'].'</td><td>'.$delete.' / '.$edit.' <!-- / update --></td></tr>';
		}
		return $result;	
	}
	
	function show_subpages($id, $depth=0){
		$content = '';
		$indent = '';
		for ($i=0 ; $i<$depth; $i++ ){
			$indent .= '&nbsp;&nbsp;';
		}
 		
		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery( '*' , 'pages', 'pid='.(int)$id.' AND deleted=0');
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)){
			$content .= $this->show_page_info($row,  $indent);
			$content .= $this->show_page_urls($row['uid'], $indent);
			if ($depth < $this->pObj->MOD_SETTINGS['depth']){
				$content .= $this->show_subpages($row['uid'], $depth+1);
			}
		}
		return $content;
	}
	
	function getLanguageIcon($id){
		if (! $this->flags){
			$this->flags = array();
		}
		if (!$this->flags[(int)$id] ){
			$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery( '*' , 'sys_language', 'uid='.(int)$id);
			if (	$row   = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)){
				$this->flags[(int)$id] = '<img src="../../../../typo3/gfx/flags/'.$row['flag'].'" />';	
			} else {
				$this->flags[(int)$id] = '';
			}
		} 
		return $this->flags[(int)$id];
	}	
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nawork_uri/modfunc_info/class.tx_naworkuri_modfunc_info.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nawork_uri/modfunc_info/class.tx_naworkuri_modfunc_info.php']);
}

?>