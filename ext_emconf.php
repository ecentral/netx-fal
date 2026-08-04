<?php

declare(strict_types=1);

/*
 * This file is part of the "netx_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

$_EXTKEY = 'netx_fal';

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
