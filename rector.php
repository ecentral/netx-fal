<?php

declare(strict_types=1);

/*
 * This file is part of the "netx_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassConst\AddTypeToConstRector;
use Rector\Php84\Rector\Foreach_\ForeachToArrayAnyRector;
use Rector\Php84\Rector\MethodCall\NewMethodCallWithoutParenthesesRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/Classes',
        __DIR__ . '/Configuration',
        __DIR__ . '/ext_emconf.php',
        __DIR__ . '/ext_localconf.php',
        __DIR__ . '/phpstan-bootstrap.php',
    ])
    ->withPhpVersion(PhpVersion::PHP_84)
    ->withSets([
        LevelSetList::UP_TO_PHP_84,
    ])
    ->withSkip([
        AddTypeToConstRector::class,
        ForeachToArrayAnyRector::class,
        NewMethodCallWithoutParenthesesRector::class,
    ])
    ->withImportNames(removeUnusedImports: true)
    ->withCache(cacheDirectory: __DIR__ . '/var/cache/rector');
