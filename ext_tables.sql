#
# Modifying pages table
#
CREATE TABLE pages (
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
	domain tinytext NOT NULL,
    path tinytext NOT NULL,
    params tinytext NOT NULL,
	hash_path tinytext NOT NULL,
	hash_params tinytext NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
	
);

