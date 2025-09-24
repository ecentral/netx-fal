<?php

use Fairway\NetXFal\Utility\Cache;

return [
    'netx_cache' => [
        'path' => '/netx/cache',
        'access' => 'public',
        'target' => Cache::class . '::clearCache',
    ],
];
