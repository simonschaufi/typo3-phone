<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Content element from TypoScript',
    'description' => 'Adds phone number functionality to TYPO3 based on Google\'s libphonenumber API.',
    'category' => 'misc',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Simon Schaufelberger',
    'author_email' => 'simonschaufi@gmail.com',
    'author_company' => '',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'php' => '7.0.0-7.2.99',
            'typo3' => '8.0.0-8.99.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
