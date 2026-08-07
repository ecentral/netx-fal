<?php

declare(strict_types=1);

/*
 * This file is part of the "netx_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\NetXFal\Utility;

use Fairway\NetXFal\Client\NetXClient;
use Fairway\NetXFal\Driver\Driver;
use TYPO3\CMS\Core\Information\Typo3Version;

class DriverUtility
{
    public static function getDriver(): string
    {
        return (new Typo3Version())->getMajorVersion() < 13
            ? implode('\\', ['Fairway', 'NetXFal', 'Driver', 'DriverV12'])
            : Driver::class;
    }

    public static function getClient(): NetXClient
    {
        $driverClass = self::getDriver();
        return $driverClass::$client;
    }
}
