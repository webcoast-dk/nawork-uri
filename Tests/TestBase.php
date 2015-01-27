<?php

namespace Nawork\NaworkUri\Tests;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Thorben Kapp <thorben@work.de>
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
 * ************************************************************* */

/**
 * Description of class
 *
 * @author Thorben Kapp <thorben@work.de>
 */
class TestBase extends \Tx_Phpunit_TestCase {

	/**
	 *
	 * @var \Nawork\NaworkUri\Utility\TransformationUtility
	 */
	protected $transformer;

	/**
	 *
	 * @var \Nawork\NaworkUri\Cache\UrlCache
	 */
	protected $cache;

	/**
	 *
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $db;

	/**
	 * @var \Nawork\NaworkUri\Configuration\ConfigurationReader
	 */
	protected $configReader;
	
	/**
	 *
	 * @var \Nawork\NaworkUri\Configuration\TableConfiguration
	 */
	protected $tableConfiguration;

	public function setUp() {
		$this->db = $GLOBALS['TYPO3_DB'];
		$this->tableConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Nawork\\NaworkUri\\Configuration\\TableConfiguration');
		\Nawork\NaworkUri\Utility\GeneralUtility::registerConfiguration('default', 'EXT:nawork_uri/Configuration/Url/TestConfiguration.xml');

		$this->setupUriTable();
		$this->setupPages();
		$this->setupNews();
		$this->setupDomain();

		$this->transformer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Nawork\NaworkUri\Utility\TransformationUtility', true, 'test.local');
		$this->cache = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Nawork\NaworkUri\Cache\UrlCache');
	}

	public function tearDown() {
		$this->db->sql_query('DROP TABLE test_pages');
		$this->db->sql_query('DROP TABLE test_tx_naworkuri_uri');
		$this->db->sql_query('DROP TABLE test_news');
		$this->db->sql_query('DROP TABLE test_sys_domain');
		unset($this->db);
		unset($this->configReader);
		unset($this->transformer);
		unset($this->cache);
	}

	protected function setupUriTable() {
		$this->tableConfiguration->setUrlTable('test_tx_naworkuri_uri');
		$this->db->sql_query("DROP TABLE IF EXISTS test_tx_naworkuri_uri;");
		$this->db->sql_query(
			"CREATE TABLE test_tx_naworkuri_uri (
				uid int(11) NOT NULL auto_increment,
				pid int(11) NOT NULL DEFAULT '0',
				page_uid int(11) DEFAULT '0' NOT NULL,
				tstamp int(11) DEFAULT '0' NOT NULL,
				crdate int(11) DEFAULT '0' NOT NULL,
				cruser_id int(11) DEFAULT '0' NOT NULL,
				sys_language_uid int(11) DEFAULT '0' NOT NULL,
				deleted tinyint(4) DEFAULT '0' NOT NULL,
				domain varchar(255) DEFAULT '' NOT NULL,
				path varchar(255) DEFAULT '' NOT NULL,
				params tinytext NOT NULL,
				hash_path varchar(32) DEFAULT '' NOT NULL,
				hash_params varchar(32) DEFAULT '' NOT NULL,
				debug_info text NOT NULL,
				locked tinyint(1) DEFAULT '0' NOT NULL,
				type tinyint(1) DEFAULT '0' NOT NULL,
				redirect_path varchar(255) DEFAULT '' NOT NULL,
				redirect_mode int(3) DEFAULT '301' NOT NULL,
				original_path varchar(255) DEFAULT '' NOT NULL,

				PRIMARY KEY (uid),
				KEY parent (pid),
				KEY domain_path (domain,hash_path)
			);");
	}

	protected function setupPages() {
		$this->tableConfiguration->setPageTable('test_pages');
		$this->db->sql_query("DROP TABLE IF EXISTS test_pages;");
		$this->db->sql_query(
				"CREATE TABLE `test_pages` (
				  `uid` int(11) NOT NULL AUTO_INCREMENT,
				  `pid` int(11) NOT NULL DEFAULT '0',
				  `t3ver_oid` int(11) NOT NULL DEFAULT '0',
				  `t3ver_id` int(11) NOT NULL DEFAULT '0',
				  `t3ver_wsid` int(11) NOT NULL DEFAULT '0',
				  `t3ver_label` varchar(255) NOT NULL DEFAULT '',
				  `t3ver_state` tinyint(4) NOT NULL DEFAULT '0',
				  `t3ver_stage` tinyint(4) NOT NULL DEFAULT '0',
				  `t3ver_count` int(11) NOT NULL DEFAULT '0',
				  `t3ver_tstamp` int(11) NOT NULL DEFAULT '0',
				  `t3ver_swapmode` tinyint(4) NOT NULL DEFAULT '0',
				  `t3ver_move_id` int(11) NOT NULL DEFAULT '0',
				  `t3_origuid` int(11) NOT NULL DEFAULT '0',
				  `tstamp` int(11) unsigned NOT NULL DEFAULT '0',
				  `sorting` int(11) unsigned NOT NULL DEFAULT '0',
				  `deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
				  `perms_userid` int(11) unsigned NOT NULL DEFAULT '0',
				  `perms_groupid` int(11) unsigned NOT NULL DEFAULT '0',
				  `perms_user` tinyint(4) unsigned NOT NULL DEFAULT '0',
				  `perms_group` tinyint(4) unsigned NOT NULL DEFAULT '0',
				  `perms_everybody` tinyint(4) unsigned NOT NULL DEFAULT '0',
				  `editlock` tinyint(4) unsigned NOT NULL DEFAULT '0',
				  `crdate` int(11) unsigned NOT NULL DEFAULT '0',
				  `cruser_id` int(11) unsigned NOT NULL DEFAULT '0',
				  `hidden` tinyint(4) unsigned NOT NULL DEFAULT '0',
				  `title` varchar(255) NOT NULL DEFAULT '',
				  `doktype` tinyint(3) unsigned NOT NULL DEFAULT '0',
				  `TSconfig` text,
				  `storage_pid` int(11) NOT NULL DEFAULT '0',
				  `is_siteroot` tinyint(4) NOT NULL DEFAULT '0',
				  `php_tree_stop` tinyint(4) NOT NULL DEFAULT '0',
				  `tx_impexp_origuid` int(11) NOT NULL DEFAULT '0',
				  `url` varchar(255) NOT NULL DEFAULT '',
				  `starttime` int(11) unsigned NOT NULL DEFAULT '0',
				  `endtime` int(11) unsigned NOT NULL DEFAULT '0',
				  `urltype` tinyint(4) unsigned NOT NULL DEFAULT '0',
				  `shortcut` int(10) unsigned NOT NULL DEFAULT '0',
				  `shortcut_mode` int(10) unsigned NOT NULL DEFAULT '0',
				  `no_cache` int(10) unsigned NOT NULL DEFAULT '0',
				  `fe_group` varchar(100) NOT NULL DEFAULT '0',
				  `subtitle` varchar(255) NOT NULL DEFAULT '',
				  `layout` tinyint(3) unsigned NOT NULL DEFAULT '0',
				  `target` varchar(80) NOT NULL DEFAULT '',
				  `media` text,
				  `lastUpdated` int(10) unsigned NOT NULL DEFAULT '0',
				  `keywords` text,
				  `cache_timeout` int(10) unsigned NOT NULL DEFAULT '0',
				  `newUntil` int(10) unsigned NOT NULL DEFAULT '0',
				  `description` text,
				  `no_search` tinyint(3) unsigned NOT NULL DEFAULT '0',
				  `SYS_LASTCHANGED` int(10) unsigned NOT NULL DEFAULT '0',
				  `abstract` text,
				  `module` varchar(10) NOT NULL DEFAULT '',
				  `extendToSubpages` tinyint(3) unsigned NOT NULL DEFAULT '0',
				  `author` varchar(255) NOT NULL DEFAULT '',
				  `author_email` varchar(80) NOT NULL DEFAULT '',
				  `nav_title` varchar(255) NOT NULL DEFAULT '',
				  `nav_hide` tinyint(4) NOT NULL DEFAULT '0',
				  `content_from_pid` int(10) unsigned NOT NULL DEFAULT '0',
				  `mount_pid` int(10) unsigned NOT NULL DEFAULT '0',
				  `mount_pid_ol` tinyint(4) NOT NULL DEFAULT '0',
				  `alias` varchar(32) NOT NULL DEFAULT '',
				  `l18n_cfg` tinyint(4) NOT NULL DEFAULT '0',
				  `fe_login_mode` tinyint(4) NOT NULL DEFAULT '0',
				  `tx_naworkuri_pathsegment` varchar(64) default '',
				  `tx_naworkuri_exclude` tinyint(1) unsigned default '0',

				  PRIMARY KEY (`uid`),
				  KEY `t3ver_oid` (`t3ver_oid`,`t3ver_wsid`),
				  KEY `parent` (`pid`,`sorting`,`deleted`,`hidden`),
				  KEY `alias` (`alias`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

		$this->db->exec_INSERTquery('test_pages', array(
			'uid' => 1,
			'pid' => 0,
			'title' => 'Home',
			'is_siteroot' => 1,
		));
		$this->db->exec_INSERTquery('test_pages', array(
			'uid' => 2,
			'pid' => 1,
			'title' => 'Sub 1',
		));
		$this->db->exec_INSERTquery('test_pages', array(
			'uid' => 3,
			'pid' => 1,
			'title' => 'Sub 2',
		));
		$this->db->exec_INSERTquery('test_pages', array(
			'uid' => 4,
			'pid' => 1,
			'title' => 'Sub 3',
		));
		$this->db->exec_INSERTquery('test_pages', array(
			'uid' => 5,
			'pid' => 2,
			'title' => 'Sub 1 1',
		));
		$this->db->exec_INSERTquery('test_pages', array(
			'uid' => 6,
			'pid' => 2,
			'title' => 'Sub 1 2',
			'tx_naworkuri_pathsegment' => 'No News',
		));
		$this->db->exec_INSERTquery('test_pages', array(
			'uid' => 7,
			'pid' => 3,
			'title' => 'Sub 2 1',
		));
		$this->db->exec_INSERTquery('test_pages', array(
			'uid' => 8,
			'pid' => 3,
			'title' => 'Sub 2 2',
		));
		$this->db->exec_INSERTquery('test_pages', array(
			'uid' => 9,
			'pid' => 3,
			'title' => 'Sub 2 3',
		));
		$this->db->exec_INSERTquery('test_pages', array(
			'uid' => 10,
			'pid' => 3,
			'title' => 'Sub 2 4',
		));
		$this->db->exec_INSERTquery('test_pages', array(
			'uid' => 11,
			'pid' => 4,
			'title' => 'Sub 3 1',
		));
		$this->db->exec_INSERTquery('test_pages', array(
			'uid' => 12,
			'pid' => 11,
			'title' => 'Sub 3 1 1?',
		));
		$this->db->exec_INSERTquery('test_pages', array(
			'uid' => 13,
			'pid' => 11,
			'title' => '?Sub 3 1 2',
		));
		$this->db->exec_INSERTquery('test_pages', array(
			'uid' => 14,
			'pid' => 11,
			'title' => '?',
		));
		$this->db->exec_INSERTquery('test_pages', array(
			'uid' => 15,
			'pid' => 11,
			'title' => 'Sub ?#? 3 1 4',
		));
		$this->db->exec_INSERTquery('test_pages', array(
			'uid' => 16,
			'pid' => 11,
			'title' => 'Sub !# 3 1 5',
		));
		$this->db->exec_INSERTquery('test_pages', array(
			'uid' => 17,
			'pid' => 11,
			'title' => 'Test-, Foo-, Bar-, und Blafasel'
		));
	}

	protected function setupNews() {
		$this->db->sql_query("DROP TABLE IF EXISTS test_news;");
		$this->db->sql_query(
				"CREATE TABLE test_news (
					uid int(11) NOT NULL auto_increment,
					pid int(11) DEFAULT '0' NOT NULL,
					title varchar(255) DEFAULT '' NOT NULL,

					PRIMARY KEY (uid),
					KEY parent (pid));");

		$this->db->exec_INSERTquery('test_news', array(
			'uid' => 1,
			'pid' => 2,
			'title' => 'News 1',
		));
		$this->db->exec_INSERTquery('test_news', array(
			'uid' => 2,
			'pid' => 2,
			'title' => 'News 2',
		));
		$this->db->exec_INSERTquery('test_news', array(
			'uid' => 3,
			'pid' => 2,
			'title' => 'News 3',
		));
	}

	protected function setupDomain() {
		$this->tableConfiguration->setDomainTable('test_sys_domain');
		$this->db->sql_query("DROP TABLE IF EXISTS test_sys_domain;");
		$this->db->sql_query(
				"CREATE TABLE `test_sys_domain` (
					`uid` int(11) unsigned NOT NULL AUTO_INCREMENT,
					`pid` int(11) unsigned NOT NULL DEFAULT '0',
					`tstamp` int(11) unsigned NOT NULL DEFAULT '0',
					`crdate` int(11) unsigned NOT NULL DEFAULT '0',
					`cruser_id` int(11) unsigned NOT NULL DEFAULT '0',
					`hidden` tinyint(4) unsigned NOT NULL DEFAULT '0',
					`domainName` varchar(80) NOT NULL DEFAULT '',
					`redirectTo` varchar(255) DEFAULT '',
					`sorting` int(10) unsigned NOT NULL DEFAULT '0',
					`prepend_params` int(10) NOT NULL DEFAULT '0',
					`redirectHttpStatusCode` int(4) unsigned NOT NULL DEFAULT '301',
					`forced` tinyint(3) unsigned NOT NULL DEFAULT '0',
					`tx_naworkuri_masterDomain` int(11) DEFAULT '0',
					`tx_multidomainpublishing_pagetype` int(11) NOT NULL DEFAULT '0',
					`tx_multidomainpublishing_mode` int(11) NOT NULL DEFAULT '0',
					PRIMARY KEY (`uid`),
					KEY `parent` (`pid`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

		$this->db->exec_INSERTquery('test_sys_domain', array(
			'pid' => '1',
			'domainName' => 'test.test',
			'sorting' => '1',
		));
		$this->db->exec_INSERTquery('test_sys_domain', array(
			'pid' => '1',
			'domainName' => 'test.local',
			'sorting' => '2',
			'tx_naworkuri_masterDomain' => 1,
		));
		$this->db->exec_INSERTquery('test_sys_domain', array(
			'pid' => '1',
			'domainName' => 'test.foo',
			'sorting' => '3',
			'tx_naworkuri_masterDomain' => 2,
		));
	}

}

?>
