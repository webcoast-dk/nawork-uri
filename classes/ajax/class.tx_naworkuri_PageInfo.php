<?php

/***************************************************************
* Copyright notice
*
* (c) 2010 Martin Ficzel <martin@work.de>
* (c) 2010 Thorben Kapp <thorben@work.de>
*
* All rights reserved
*
* This script is part of the Caretaker project. The Caretaker project
* is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Ajax methods which are used as ajaxID-methods by the
 * naworkuri management backend-module.
 *
 * @author Martin Ficzel <martin@work.de>
 * @author Thorben Kapp	<thorben@work.de>
 *
 * @package TYPO3
 * @subpackage nawork_uri
 */
require_once(PATH_typo3.'class.db_list.inc');
require_once(t3lib_extMgm::extPath('cms').'layout/class.tx_cms_layout.php');

class tx_naworkuri_PageInfo {
	public function getPageInfo($params, &$ajaxObj) {
		global $LANG, $BE_USER;
		/* @var $LANG language */
		$LANG->init($BE_USER->uc['lang']);
		$LANG->includeLLFile(t3lib_div::getFileAbsFileName('EXT:nawork_uri/mod1/locallang.xml'), 1);
		$pageId = t3lib_div::GPvar('page');
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*','pages','uid='.intval($pageId));
		if(count($res) > 0) {
			$page = $res[0];

			$infoArray = array('uid', 'title', 'subtitle', 'nav_title','alias');
			$html = '<div class="pageInfo">';
			foreach($infoArray as $infoField) {
				if(!empty($page[$infoField])) {
					$html .= '<div class="pageInfo-infoField '.$infoField.'"><span class="label">'.$LANG->getLL('page.'.$infoField).'</span><span class="info">'.$page[$infoField].'</span></div>';
				}
			}
			$html .= '</div>';
		} else {
			$html = '<div class="message">No pid given. Please click on a page in the page tree.</div>';
		}

		echo $html;
	}

	public function getPageUris($params, &$ajaxObj) {
		global $LANG, $BE_USER;

		$pid = intval(t3lib_div::GPvar('page'));
		$start = intval(t3lib_div::GPvar('start'));
		$limit = intval(t3lib_div::GPvar('limit'));

		if($pid > 0) {
//			$query = $GLOBALS['TYPO3_DB']->SELECTquery('u.uid as uid,u.path as path,u.params as params,u.sticky as sticky,u.hidden as hidden,u.domain as domain,(SELECT flag FROM sys_language WHERE sys_language.uid = u.sys_language_uid AND u.sys_language_uid > 0) as flag','tx_naworkuri_uri u,sys_language l','u.pid='.intval(t3lib_div::GPvar('page')).' AND u.deleted=0 AND u.pid>0');
//			echo $query;
			$uris = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('u.uid as uid,u.path as path,u.params as params,u.sticky as sticky,u.hidden as hidden,u.domain as domain,(SELECT flag FROM sys_language l WHERE l.uid = u.sys_language_uid AND u.sys_language_uid > 0) as flag','tx_naworkuri_uri u','u.pid='.$pid.' AND u.deleted=0 AND u.pid>0','','path ASC');
			$total = count($uris);
			if($limit > 0) {
				$tempUris = array();
				for($i=0; $i<count($uris);$i++) {
					if($i>=$start && $i<$start+$limit) {
						$tempUris[] = $uris[$i];
					}
				}
				$uris = $tempUris;
			}
			$output = "{\n\turls: [\n";
			foreach($uris as $uri) {
				$output .= "\t\t{";
				$output .= "icon: '".t3lib_iconWorks::getIcon('tx_naworkuri_uri', $uri)."', ";
				$output .= "uid: ".$uri['uid'].", ";
				$output .= "url: '".$uri['path']."', ";
				$output .= "flag: '".$uri['flag']."', ";
				$output .= "sticky: '".$uri['sticky']."', ";
				$output .= "domain: '".$uri['domain']."', ";
				$output .= "params: '".$uri['params']."', ";
				$output .= "hidden: '".$uri['hidden']."'";
				$output .= "},\n";
			}
			$output .= "\t],";
			$output .= "totalCount: ".$total."\n}";
		} else {
			$output = "{\n\turls: []\n}";
		}

		
		echo $output;
	}

	public function modPageUris($params, &$ajaxObj) {
		$mode = t3lib_div::GPvar('mode');
		$uid = t3lib_div::GPvar('uid');
		switch($mode) {
			case 'delete':
				/* @var $tceMain t3lib_TCEmain */
				$tceMain = t3lib_div::makeInstance('t3lib_TCEmain');
				$tceMain->deleteRecord('tx_naworkuri_uri', intval($uid));
				break;
			case 'sticky':
				/* @var $tceMain t3lib_TCEmain */
				$tceMain = t3lib_div::makeInstance('t3lib_TCEmain');
				$tceMain->updateDB('tx_naworkuri_uri', intval($uid), array('sticky' => 1));
				$tceMain->process_datamap();
			case 'unsticky':
				/* @var $tceMain t3lib_TCEmain */
				$tceMain = t3lib_div::makeInstance('t3lib_TCEmain');
				$tceMain->updateDB('tx_naworkuri_uri', intval($uid), array('sticky' => 0));
				break;
			case 'hide':
				/* @var $tceMain t3lib_TCEmain */
				$tceMain = t3lib_div::makeInstance('t3lib_TCEmain');
				$tceMain->updateDB('tx_naworkuri_uri', intval($uid), array('hidden' => 1));
			break;
			case 'unhide':
				/* @var $tceMain t3lib_TCEmain */
				$tceMain = t3lib_div::makeInstance('t3lib_TCEmain');
				$tceMain->updateDB('tx_naworkuri_uri', intval($uid), array('hidden' => 0));
				break;
		}
	}

	public function searchPageUris($params, &$ajaxObj) {
		$searchWord = str_replace('*','%',urldecode(t3lib_div::GPvar('search')));
		$searchType = t3lib_div::GPvar(('type'));
		$start = intval(t3lib_div::GPvar('start'));
		$limit = intval(t3lib_div::GPvar('limit'));
		$pid = intval(t3lib_div::GPvar('page'));

		if(!empty($searchWord)) {
			$searchWord = $GLOBALS['TYPO3_DB']->fullQuoteStr('%'.$searchWord.'%');
			$uris = array();
			switch($searchType) {
				case -1:
					$uris = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('u.uid as uid,u.path as path,u.params as params,u.sticky as sticky,u.hidden as hidden,u.domain as domain,(SELECT flag FROM sys_language l WHERE l.uid = u.sys_language_uid AND u.sys_language_uid > 0) as flag','tx_naworkuri_uri u','u.path LIKE '.$searchWord.' AND u.deleted=0 AND u.pid>0','','path ASC');
					break;
				case 0:
					if($pid > 0) {
						$uris = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('u.uid as uid,u.path as path,u.params as params,u.sticky as sticky,u.hidden as hidden,u.domain as domain,(SELECT flag FROM sys_language l WHERE l.uid = u.sys_language_uid AND u.sys_language_uid > 0) as flag','tx_naworkuri_uri u','u.path LIKE '.$searchWord.' AND u.deleted=0 AND u.pid='.$pid,'','path ASC');
					}
					break;
				case 1:
					/* @var $tx_cms_layout tx_cms_layout */
					$tx_cms_layout = t3lib_div::makeInstance('tx_cms_layout');
					$pages = $tx_cms_layout->pages_getTree(array(), $pid, '', '', 1);
					$pidList = array();
					foreach($pages as $p) {
						$pidList[] = $p['uid'];
					}
					$uris = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('u.uid as uid,u.path as path,u.params as params,u.sticky as sticky,u.hidden as hidden,u.domain as domain,(SELECT flag FROM sys_language l WHERE l.uid = u.sys_language_uid AND u.sys_language_uid > 0) as flag','tx_naworkuri_uri u','u.path LIKE '.$searchWord.' AND u.deleted=0 AND u.pid IN ('.implode(',', $pidList).')','','path ASC');
					break;
				case 2:
					/* @var $tx_cms_layout tx_cms_layout */
					$tx_cms_layout = t3lib_div::makeInstance('tx_cms_layout');
					$pages = $tx_cms_layout->pages_getTree(array(), $pid, '', '', 2);
					$pidList = array();
					foreach($pages as $p) {
						$pidList[] = $p['uid'];
					}
					$uris = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('u.uid as uid,u.path as path,u.params as params,u.sticky as sticky,u.hidden as hidden,u.domain as domain,(SELECT flag FROM sys_language l WHERE l.uid = u.sys_language_uid AND u.sys_language_uid > 0) as flag','tx_naworkuri_uri u','u.path LIKE '.$searchWord.' AND u.deleted=0 AND u.pid IN ('.implode(',', $pidList).')','','path ASC');
					break;
				case 3:
					/* @var $tx_cms_layout tx_cms_layout */
					$tx_cms_layout = t3lib_div::makeInstance('tx_cms_layout');
					$pages = $tx_cms_layout->pages_getTree(array(), $pid, '', '', 3);
					$pidList = array();
					foreach($pages as $p) {
						$pidList[] = $p['uid'];
					}
					$uris = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('u.uid as uid,u.path as path,u.params as params,u.sticky as sticky,u.hidden as hidden,u.domain as domain,(SELECT flag FROM sys_language l WHERE l.uid = u.sys_language_uid AND u.sys_language_uid > 0) as flag','tx_naworkuri_uri u','u.path LIKE '.$searchWord.' AND u.deleted=0 AND u.pid IN ('.implode(',', $pidList).')','','path ASC');
					break;
			}
			
			$total = count($uris);
			if($limit > 0) {
				$tempUris = array();
				for($i=0; $i<count($uris);$i++) {
					if($i>=$start && $i<$start+$limit) {
						$tempUris[] = $uris[$i];
					}
				}
				$uris = $tempUris;
			}
			$output = "{\n\turls: [\n";
			foreach($uris as $uri) {
				$output .= "\t\t{";
				$output .= "icon: '".t3lib_iconWorks::getIcon('tx_naworkuri_uri', $uri)."', ";
				$output .= "uid: ".$uri['uid'].", ";
				$output .= "url: '".$uri['path']."', ";
				$output .= "flag: '".$uri['flag']."', ";
				$output .= "sticky: '".$uri['sticky']."', ";
				$output .= "domain: '".$uri['domain']."', ";
				$output .= "params: '".$uri['params']."', ";
				$output .= "hidden: '".$uri['hidden']."'";
				$output .= "},\n";
			}
			$output .= "\t], ";
			$output .= "totalCount: ".$total."\n}";
		} else {
			$output = "{\n\turls: []\n}";
		}


		echo $output;
	}
}
?>
