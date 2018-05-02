<?php

$EM_CONF[$_EXTKEY] = [
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
    'version' => '2.0.0',
    'state' => 'stable',
    'constraints' => [
        'depends' => [
            'typo3' => '7.6.0-7.99.99',
            'solr' => '5.0.*',
            'solrfluid' => '1.0.*',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    "_md5_values_when_last_written" => '',
];