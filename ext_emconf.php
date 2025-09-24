<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'NetX (FAL)',
    'description' => 'Provides a FAL driver for the NetX DAM.',
    'category' => 'plugin',
    'author' => 'Denis Doerner',
    'author_email' => 'support@ecentral.de',
    'author_company' => 'E-Central GmbH',
    'state' => 'stable',
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.0.0-13.99.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
