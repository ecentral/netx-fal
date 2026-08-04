<?php

declare(strict_types=1);

/*
 * This file is part of the "netx_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Fairway\NetXFal\Utility\Cache;

return [
    'netx_cache' => [
        'path' => '/netx/cache',
        'access' => 'public',
        'target' => Cache::class . '::clearCache',
    ],
];
