<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 phone number',
    'description' => 'Adds phone number functionality to TYPO3 based on Google\'s libphonenumber API.',
    'category' => 'misc',
    'state' => 'stable',
    'author' => 'Simon Schaufelberger',
    'author_email' => 'simonschaufi+typo3phone@gmail.com',
    'author_company' => '',
    'version' => '3.1.1',
    'constraints' => [
        'depends' => [
            'php' => '8.2.0-8.4.99',
            'typo3' => '13.4.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
