#
# Modifying pages table
#

CREATE TABLE pages (
	tx_naworkuri_pathsegment varchar(64) default '',
	tx_naworkuri_exclude tinyint(1) unsigned default '0'
);

#
# Modifying pages_language_overlay table
#

CREATE TABLE pages_language_overlay (
	tx_naworkuri_pathsegment varchar(64) default '',
	tx_naworkuri_exclude tinyint(1) unsigned default '0'
);

#
# Modify sys_domain
#

CREATE TABLE sys_domain (
	tx_naworkuri_masterDomain int(11) DEFAULT '0',
	tx_naworkuri_use_configuration varchar(200) NOT NULL DEFAULT ''
);

#
# Table structure for table 'tx_naworkuri_uri'
#

CREATE TABLE tx_naworkuri_uri (
  uid int(11) NOT NULL auto_increment,
	pid int(11) NOT NULL DEFAULT '0',
  page_uid int(11) DEFAULT '0' NOT NULL,
  tstamp int(11) DEFAULT '0' NOT NULL,
  crdate int(11) DEFAULT '0' NOT NULL,
  cruser_id int(11) DEFAULT '0' NOT NULL,
  sys_language_uid int(11) DEFAULT '0' NOT NULL,
  domain varchar(255) DEFAULT '' NOT NULL,
  path varchar(1000) DEFAULT '' NOT NULL,
	path_hash varchar(32) DEFAULT '' NOT NULL,
	parameters text NOT NULL,
	parameters_hash varchar(32) DEFAULT '' NOT NULL,
	locked tinyint(1) DEFAULT '0' NOT NULL,
	type tinyint(1) DEFAULT '0' NOT NULL,
  redirect_path varchar(500) NOT NULL DEFAULT '',
  redirect_mode int(3) DEFAULT '301' NOT NULL,
	original_path varchar(255) DEFAULT '' NOT NULL,

  PRIMARY KEY (uid),
  KEY parent (pid),
  UNIQUE KEY domain_path (domain,path_hash),
	KEY cache (page_uid,sys_language_uid,domain,parameters_hash,type),
	KEY path_hash (path_hash),
	KEY unique_parameters (type,parameters_hash)
);
