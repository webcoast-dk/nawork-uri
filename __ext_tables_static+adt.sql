
#
# Table structure for table 'link_cache'
#

DROP TABLE IF EXISTS link_cache;
CREATE TABLE `link_cache` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `params` blob,
  `url` text,
  `tstamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `crdatetime` datetime default NULL,
  `sticky` tinyint(1) unsigned default 0,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `url` (`url`(255))
) ENGINE=MyISAM;



#
# Table structure for table 'link_oldlinks'
#

DROP TABLE IF EXISTS link_oldlinks;
CREATE TABLE `link_oldlinks` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `link_id` int(10) unsigned NOT NULL default '0',
  `url` text,
  `tstamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `url` (`url`(255))
) ENGINE=MyISAM;
