#
# Modifying pages table
#
CREATE TABLE pages (
	tx_naworkuri_pathsegment varchar(30) default '',
	tx_naworkuri_exclude tinyint(1) unsigned default '0'
); 

#
# Modifying pages_language_overlay table
#
CREATE TABLE pages_language_overlay (
	tx_naworkuri_pathsegment varchar(30) default '',
	tx_naworkuri_exclude tinyint(1) unsigned default '0'
);

#
# Table structure for table 'tx_naworkuri_uri'
#
CREATE TABLE tx_naworkuri_uri (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    t3_origuid int(11) DEFAULT '0' NOT NULL,
    sys_language_uid int(11) DEFAULT '0' NOT NULL,
    l18n_parent int(11) DEFAULT '0' NOT NULL,
    l18n_diffsource mediumblob NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden  tinyint(4) DEFAULT '0' NOT NULL,
	domain varchar(255) NOT NULL,
    path varchar(255) NOT NULL,
    params tinytext NOT NULL,
	hash_path char(32) NOT NULL,
	hash_params char(32) NOT NULL,
	debug_info text NOT NULL,
	sticky tinyint(4) DEFAULT '0' NOT NULL,
	
    PRIMARY KEY (uid),
    KEY parent (pid),
    KEY domain_path (domain, hash_path)
    
);

