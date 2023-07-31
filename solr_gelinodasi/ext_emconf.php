<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Solr for gelinodasi',
    'description' => '',
    'category' => '',
    'author' => '',
    'author_email' => '',
    'author_company' => '',
    'state' => 'alpha',
    'clearCacheOnLoad' => 1,
    'version' => '0.1.0',
    'constraints' =>
        [
            'depends' =>
                [
                    'typo3' => '9.5.0-11.5.99',
                    'solr' => '10.0.0-11.99.99'
                ],
        ],
    'autoload' =>
        [
            'psr-4' =>
                [
                    'Tagdo\\SolrGelinodasi\\' => 'Classes',
                ],
        ]
];
