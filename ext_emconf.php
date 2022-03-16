<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 phone number',
    'description' => 'Adds phone number functionality to TYPO3 based on Google\'s libphonenumber API.',
    'category' => 'misc',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Simon Schaufelberger',
    'author_email' => 'simonschaufi+typo3phone@gmail.com',
    'author_company' => '',
    'version' => '2.0.0',
    'constraints' => [
        'depends' => [
            'php' => '7.4.0-8.0.99',
            'typo3' => '10.0.0-11.99.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
