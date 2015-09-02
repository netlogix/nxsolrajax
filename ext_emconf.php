<?php

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Netlogix Solr Ajax',
	'description' => '',
	'category' => 'libs',
	'author' => 'Sascha Nowak',
	'author_email' => 'sascha.nowak@netlogix.de',
	'shy' => '',
	'dependencies' => '',
	'conflicts' => '',
	'module' => '',
	'internal' => '',
	'uploadFolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'version' => '1.0.1',
	'state' => 'stable',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.2.0-7.99.99',
			'nxcrudextbase' => '1.0.0',
			'solr' => '3.0.*',
		),
		'conflicts' => array(),
		'suggests' => array(),
	),
	"_md5_values_when_last_written" => '',
);

?>