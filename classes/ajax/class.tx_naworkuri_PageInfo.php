<?php

/* * *************************************************************
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
 * ************************************************************* */

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
require_once(PATH_typo3 . 'class.db_list.inc');
require_once(t3lib_extMgm::extPath('cms') . 'layout/class.tx_cms_layout.php');

class tx_naworkuri_PageInfo {

	/**
	 *
	 * @var t3lib_db
	 */
	protected $db;

	public function __construct() {
		$this->db = $GLOBALS['TYPO3_DB'];
	}

	public function getPageInfo($params, &$ajaxObj) {
		global $LANG, $BE_USER;
		/* @var $LANG language */
		$LANG->init($BE_USER->uc['lang']);
		$LANG->includeLLFile(t3lib_div::getFileAbsFileName('EXT:nawork_uri/mod1/locallang.xml'), 1);
		$pageId = t3lib_div::_GP('page');
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'pages', 'uid=' . intval($pageId));
		if (count($res) > 0) {
			$page = $res[0];

			$infoArray = array('uid', 'title', 'subtitle', 'nav_title', 'alias');
			$html = '<div class="pageInfo">';
			foreach ($infoArray as $infoField) {
				if (!empty($page[$infoField])) {
					$html .= '<div class="pageInfo-infoField ' . $infoField . '"><span class="label">' . $LANG->getLL('page.' . $infoField) . '</span><span class="info">' . $page[$infoField] . '</span></div>';
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

		$pid = intval(t3lib_div::_GP('page'));
		$start = intval(t3lib_div::_GP('start'));
		$limit = intval(t3lib_div::_GP('limit'));
		$url = t3lib_div::_GP('url');
		$locked = intval(t3lib_div::_GP('locked'));
		$type = intval(t3lib_div::_GP('type'));
		$language = intval(t3lib_div::_GP('language'));
		$andWhere = array();
		$andWhereString = '';
		if (strlen($url) > 0) {
			$andWhere[] = 'path LIKE \'%' . $url . '%\'';
		}
		if ($locked > -1) {
			$andWhere[] = 'locked=' . $locked;
		}
		if ($type > -1) {
			$andWhere[] = 'type=' . $type;
		}
		if ($language > -1) {
			$andWhere[] = 'sys_language_uid=' . $language;
		}
		if (count($andWhere)) {
			$andWhereString = ' AND ' . implode(' AND ', $andWhere);
		}

		$output = '';
		if ($pid > 0) {
			$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = 1;
			$uriRes = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('u.uid as uid,u.path as path,u.redirect_path as redirect_path,u.params as params,u.locked as locked,u.domain as domain,u.type as type,(SELECT flag FROM sys_language l WHERE l.uid = u.sys_language_uid AND u.sys_language_uid > 0) as flag', 'tx_naworkuri_uri u', '((u.page_uid=' . intval($pid) . ' AND u.type!=2 AND u.page_uid>0) OR u.type=2)' . $andWhereString, '', 'path ASC');
			$total = count($uriRes);
			$urls = array();
			$counter = 0;
			foreach ($uriRes as $u) {
				if (($limit > 0  && $counter >= $start && $counter <= ($start + $limit)) || $limit < 1) {
					/* evaluate icon */
					$iconFileName = 'uri';
					switch ($u['type']) {
						case 1:
							$iconFileName .= '_old';
							break;
						case 2:
							$iconFileName .= '_redirect';
							break;
						default:
							if ($u['locked'] == 1) {
								$iconFileName .= '_locked';
							}
							break;
					}
					$iconFileName .= '.png';
					$urls[] = array(
						'uid' => intval($u['uid']),
						'path' => $u['path'],
						'redirect_path' => $u['redirect_path'],
						'params' => $u['params'],
						'locked' => intval($u['locked']),
						'domain' => $u['domain'],
						'icon' => t3lib_extMgm::extRelPath('nawork_uri') . "Resources/GFX/Icons/" . $iconFileName,
						'flag' => $u['flag']
					);
				}
				++$counter;
			}
			$output = json_encode(array('urls' => $urls, 'totalCount' => $total));
//			$output = "{\n\turls: [\n";
//			foreach ($uris as $uri) {
//				$output .= "\t\t{";
//				/* evaluate icon */
//				$iconFileName = 'uri';
//				switch ($uri['type']) {
//					case 1:
//						$iconFileName .= '_old';
//						break;
//					case 2:
//						$iconFileName .= '_redirect';
//						break;
//					default:
//						if ($uri['locked'] == 1) {
//							$iconFileName .= '_locked';
//						}
//						break;
//				}
//				$iconFileName .= '.png';
////				$output .= "icon: '" . t3lib_extMgm::extRelPath('nawork_uri') . "Resources/GFX/Icons/" . $iconFileName . "', ";
//				$output .= "icon: " . $uri['icon'] . ", ";
//				$output .= "uid: " . $uri['uid'] . ", ";
//				$output .= "url: '" . $uri['path'] . "', ";
//				$output .= "flag: '" . $uri['flag'] . "', ";
//				$output .= "locked: '" . $uri['locked'] . "', ";
//				$output .= "domain: '" . $uri['domain'] . "', ";
//				$output .= "params: '" . $uri['params'] . "', ";
//				$output .= "hidden: '" . $uri['hidden'] . "'";
//				$output .= "},\n";
//			}
//			$output .= "\t],";
//			$output .= "totalCount: " . $total . "\n";
//			$output .= "debug: '" . $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery . "'";
//			$output .= "}";
		} else {
//			$output = "{\n\turls: []\n}";
			$output = json_encode(array('urls' => array(), 'totalCount' => 0));
		}


		echo $output;
	}

	public function modPageUris($params, &$ajaxObj) {
		$mode = t3lib_div::_GP('mode');
		$uid = t3lib_div::_GP('uid');
		$db = $GLOBALS['TYPO3_DB'];
		/* @var $db t3lib_db */

		switch ($mode) {
			case 'delete':
				$db->exec_DELETEquery('tx_naworkuri_uri', 'uid='.intval($uid));
				break;
			case 'delete_multiple':
				$uids = t3lib_div::trimExplode(',', t3lib_div::_GP('uids'));
				$records = array();
				foreach ($uids as $u) {
					$records[] = 'uid='.intval($u);
				}
				$db->exec_DELETEquery('tx_naworkuri_uri', implode(' OR ', $records));
				break;
			case 'lock':
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_naworkuri_uri', 'uid=' . intval($uid), array('locked' => 1), array('locked'));
				break;
			case 'unlock':
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_naworkuri_uri', 'uid=' . intval($uid), array('locked' => 0), array('locked'));
				break;
		}
	}

	public function searchPageUris($params, &$ajaxObj) {
		$searchWord = str_replace('*', '%', urldecode(t3lib_div::_GP('search')));
		$searchType = t3lib_div::_GP(('type'));
		$start = intval(t3lib_div::_GP('start'));
		$limit = intval(t3lib_div::_GP('limit'));
		$pid = intval(t3lib_div::_GP('page'));

		if (!empty($searchWord)) {
			$searchWord = $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $searchWord . '%');
			$uris = array();
			switch ($searchType) {
				case -1:
					$uris = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('u.uid as uid,u.path as path,u.params as params,u.sticky as sticky,u.hidden as hidden,u.domain as domain,(SELECT flag FROM sys_language l WHERE l.uid = u.sys_language_uid AND u.sys_language_uid > 0) as flag', 'tx_naworkuri_uri u', 'u.path LIKE ' . $searchWord . ' AND u.deleted=0 AND u.pid>0', '', 'path ASC');
					break;
				case 0:
					if ($pid > 0) {
						$uris = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('u.uid as uid,u.path as path,u.params as params,u.sticky as sticky,u.hidden as hidden,u.domain as domain,(SELECT flag FROM sys_language l WHERE l.uid = u.sys_language_uid AND u.sys_language_uid > 0) as flag', 'tx_naworkuri_uri u', 'u.path LIKE ' . $searchWord . ' AND u.deleted=0 AND u.pid=' . $pid, '', 'path ASC');
					}
					break;
				case 1:
					/* @var $tx_cms_layout tx_cms_layout */
					$tx_cms_layout = t3lib_div::makeInstance('tx_cms_layout');
					$pages = $tx_cms_layout->pages_getTree(array(), $pid, '', '', 1);
					$pidList = array();
					foreach ($pages as $p) {
						$pidList[] = $p['uid'];
					}
					$uris = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('u.uid as uid,u.path as path,u.params as params,u.sticky as sticky,u.hidden as hidden,u.domain as domain,(SELECT flag FROM sys_language l WHERE l.uid = u.sys_language_uid AND u.sys_language_uid > 0) as flag', 'tx_naworkuri_uri u', 'u.path LIKE ' . $searchWord . ' AND u.deleted=0 AND u.pid IN (' . implode(',', $pidList) . ')', '', 'path ASC');
					break;
				case 2:
					/* @var $tx_cms_layout tx_cms_layout */
					$tx_cms_layout = t3lib_div::makeInstance('tx_cms_layout');
					$pages = $tx_cms_layout->pages_getTree(array(), $pid, '', '', 2);
					$pidList = array();
					foreach ($pages as $p) {
						$pidList[] = $p['uid'];
					}
					$uris = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('u.uid as uid,u.path as path,u.params as params,u.sticky as sticky,u.hidden as hidden,u.domain as domain,(SELECT flag FROM sys_language l WHERE l.uid = u.sys_language_uid AND u.sys_language_uid > 0) as flag', 'tx_naworkuri_uri u', 'u.path LIKE ' . $searchWord . ' AND u.deleted=0 AND u.pid IN (' . implode(',', $pidList) . ')', '', 'path ASC');
					break;
				case 3:
					/* @var $tx_cms_layout tx_cms_layout */
					$tx_cms_layout = t3lib_div::makeInstance('tx_cms_layout');
					$pages = $tx_cms_layout->pages_getTree(array(), $pid, '', '', 3);
					$pidList = array();
					foreach ($pages as $p) {
						$pidList[] = $p['uid'];
					}
					$uris = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('u.uid as uid,u.path as path,u.params as params,u.sticky as sticky,u.hidden as hidden,u.domain as domain,(SELECT flag FROM sys_language l WHERE l.uid = u.sys_language_uid AND u.sys_language_uid > 0) as flag', 'tx_naworkuri_uri u', 'u.path LIKE ' . $searchWord . ' AND u.deleted=0 AND u.pid IN (' . implode(',', $pidList) . ')', '', 'path ASC');
					break;
			}

			$total = count($uris);
			if ($limit > 0) {
				$tempUris = array();
				for ($i = 0; $i < count($uris); $i++) {
					if ($i >= $start && $i < $start + $limit) {
						$tempUris[] = $uris[$i];
					}
				}
				$uris = $tempUris;
			}
			$output = "{\n\turls: [\n";
			foreach ($uris as $uri) {
				$output .= "\t\t{";
				$output .= "icon: '" . t3lib_iconWorks::getIcon('tx_naworkuri_uri', $uri) . "', ";
				$output .= "uid: " . $uri['uid'] . ", ";
				$output .= "url: '" . $uri['path'] . "', ";
				$output .= "flag: '" . $uri['flag'] . "', ";
				$output .= "sticky: '" . $uri['sticky'] . "', ";
				$output .= "domain: '" . $uri['domain'] . "', ";
				$output .= "params: '" . $uri['params'] . "', ";
				$output .= "hidden: '" . $uri['hidden'] . "'";
				$output .= "},\n";
			}
			$output .= "\t], ";
			$output .= "totalCount: " . $total . "\n}";
		} else {
			$output = "{\n\turls: []\n}";
		}


		echo $output;
	}

	public function getLanguages() {
		$languages = array(
			array(
				'uid' => -1,
				'label' => 'All'
			),
			array(
				'uid' => 0,
				'label' => 'Default'
			)
		);
		$res = $this->db->exec_SELECTgetRows('uid,title', 'sys_language', 'hidden=0');
		if (count($res) > 0) {
			foreach ($res as $lang) {
				$languages[] = array(
					'uid' => $lang['uid'],
					'label' => $lang['title']
				);
			}
		}
		echo json_encode(array('languages' => $languages, 'totalCount' => count($languages)));
	}

}

?>
