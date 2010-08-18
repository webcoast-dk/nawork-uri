<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2010 Martin Ficzel (martin@work.de)
*  (c) 2010 Thorben Kapp (thorben@work.de)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is 
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
/** 
 * Module 'n@work URI Management' for the 'nawork_uri' extension.
 *
 * Manage the urls created and used by the nawork_uri extension.
 *
 * @author	Martin Ficzel <martin@work.de>
 * @author	Thorben Kapp <thorben@work.de>
 */

	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');

$GLOBALS['LANG']->includeLLFile('EXT:nawork_uri/mod1/locallang.xml');
$GLOBALS['BE_USER']->modAccess($MCONF, 1);

class tx_naworkuri_module1 extends t3lib_SCbase {
	var $pageinfo;
	var $uriRepository;
	var $extPath;
	/**
	 *
	 * @var template
	 */
	var $doc;
	/**
	 *
	 * @var t3lib_PageRenderer
	 */
	var $pageRenderer;

	/**
	 * Initializes the Module
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$TYPO3_CONF_VARS;
		$this->extPath = $BACK_PATH.'../typo3conf/ext/nawork_uri/';
		
		parent::init();
	}


	/**
	 * Main function of the module. Write the content to $this->content
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS,$scriptfile;

		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;

		$this->pageRenderer = $this->doc->getPageRenderer();
//		$this->pageRenderer->disableCompressJavascript();
//		$this->pageRenderer->enableConcatenateFiles();
		$this->pageRenderer->loadExtJS();
		$this->pageRenderer->enableExtJSQuickTips();
		$this->pageRenderer->addJsFile($this->extPath.'resources/javascript/Ext.grid.ObservableColumn.js');
		$this->pageRenderer->addJsFile($this->extPath.'resources/javascript/Ext.grid.CheckColumn.js');
		$this->pageRenderer->addJsFile($this->extPath.'resources/javascript/Ext.grid.ButtonColumn.js');
		$this->pageRenderer->addJsFile($this->extPath.'resources/javascript/Ext.ux.grid.RowActions.js');
		$this->pageRenderer->addJsFile($this->extPath.'resources/javascript/tx.naworkuri.js', 'text/javascript', false);
		$this->pageRenderer->addJsFile($this->extPath.'resources/javascript/tx.naworkuri.pageinfo.js');
		$this->pageRenderer->addJsFile($this->extPath.'resources/javascript/tx.naworkuri.urisearch.js');
		$this->pageRenderer->addJsFile($this->extPath.'resources/javascript/tx.naworkuri.pageuris.js');
		$this->pageRenderer->addCssFile($this->extPath.'resources/css/naworkuri_be.css');

		$this->pageRenderer->addJsInlineCode('nawork_uri',
			'
			Ext.state.Manager.setProvider(new Ext.state.CookieProvider());
			Ext.onReady(function() {
				var searchPanel = new tx.naworkuri.UriSearch({
					id: "naworkuri-urisearch",
					title: "'.$LANG->getLL('uriSearch').'",
					backPath: "'.$this->doc->backPath.'",
					border: false,
					page: "'.$this->id.'"
				});
				var uriPanel = new tx.naworkuri.PageUris({
					id: "naworkuri-pageuris",
					title: "'.$LANG->getLL('pageUris').'",
					backPath: "'.$this->doc->backPath.'",
					border: false,
					page: "'.$this->id.'"
				});
//				searchPanel.addListener("beforecollapse", function() {
//					uriPanel.collapse();
//				});
//				uriPanel.addListener("beforecollapse", function() {
//					searchPanel.collapse();
//				});

				tx.naworkuri.view = new Ext.Viewport({
					layout: "fit",
					title: "n@work URI Management",
					items: {
						id: "naworkuri-management",
						xtype: "panel",
                        autoScroll: true,
						title: "'.$LANG->getLL('title').'",
//						autoScroll: true,
						items: [
//							{
//								id: "naworkuri-pageinfo",
//								xtype: "naworkuri-pageinfo",
//								title: "'.$LANG->getLL('pageInfo').'",
//								page: "'.$this->id.'",
//								backPath: "'.$this->doc->backPath.'",
//								border: false
//							},
							uriPanel,
							searchPanel
						]
					}
				});
			});
			');

		$this->content .= $this->doc->startPage($GLOBALS['LANG']->getLL('title'));
		$this->doc->form = '';

		/*
		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;
		
		// if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))	{
		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id) || ($BE_USER->user['uid'] && !$this->id)) {
	
				// Draw the header.
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;
			$this->doc->form='<form action="" method="POST">';

				// JavaScript
			$this->doc->JScode = '
				<script language="javascript">
					script_ended = 0;
					function jumpToUrl(URL)	{
						document.location = URL;
					}
				</script>
			';
			/* unused - is for web module ?!
			$this->doc->postCode='
				<script language="javascript">
					script_ended = 1;
					if (top.theMenu) top.theMenu.recentuid = "'.intval($this->id).'";
				</script>
			';
*/
#			$headerSection = $this->doc->getHeader('pages',$this->pageinfo,$this->pageinfo['_thePath']).'<br />'.$LANG->php3Lang['labels']['path'].': '.t3lib_div::fixed_lgd_pre($this->pageinfo['_thePath'],50);
/*
			$this->content.= $this->doc->startPage($LANG->getLL('title'));
			$this->content.= $this->doc->header($LANG->getLL('title'));
			$this->content.= $this->doc->spacer(5);
#			$this->content.= $this->doc->section('',$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function'])));
#			$this->content.= $this->doc->divider(5);

			
			// Render content:
			$this->moduleContent();

			
			// ShortCut
#			if ($BE_USER->mayMakeShortcut())	{
#				$this->content.= $this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
#			}
		
			$this->content.= $this->doc->spacer(10);
		} else {
				// If no access or if ID == zero
		
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;
		
			$this->content.= $this->doc->startPage($LANG->getLL('title'));
			$this->content.= $this->doc->header($LANG->getLL('title'));
			$this->content.= $this->doc->spacer(5);
			$this->content.= $this->doc->spacer(10);
		}*/
	}
	function printContent()	{
		$this->content.= $this->doc->endPage();
		echo $this->content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cc_awstats/mod1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cc_awstats/mod1/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_naworkuri_module1');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>
