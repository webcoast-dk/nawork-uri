<?php

########################################################################
# Extension Manager/Repository config file for ext "nawork_uri".
#
# Auto generated 24-05-2013 13:42
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'n@work URI',
	'description' => 'Create nice looking URLs like RealURL or CoolURI. It follows the concept of cooluri creating and caching the urls. Requirements: PHP 5+ with SimpleXML, cURL! MySQL 4.1+.',
	'category' => 'fe',
	'shy' => 0,
	'version' => '3.0.0-dev',
	'dependencies' => 'extbase,fluid',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'mod1',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Thorben Nissen',
	'author_email' => 'thorben@work.de',
	'author_company' => 'n@work',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'typo3' => '7.6.0-7.6.99',
			'extbase' => '',
			'fluid' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'suggests' => array(
	),
);

?>
