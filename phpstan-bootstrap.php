<?php

declare(strict_types=1);

/*
 * This file is part of the "netx_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

if (!class_exists(\Fairway\NetXFal\Driver\DriverV12::class, false)) {
    class_alias(\Fairway\NetXFal\Driver\Driver::class, \Fairway\NetXFal\Driver\DriverV12::class);
}
