#
# Modifying pages table
#
CREATE TABLE pages (
	tx_realurl_pathsegment varchar(30) default '',
	tx_cooluri_exclude tinyint(1) unsigned default '0'
); 
