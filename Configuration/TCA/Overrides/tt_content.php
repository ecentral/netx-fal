<?php

declare(strict_types=1);

/*
 * This file is part of the "netx_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Fairway\NetXFal\Utility\Cache;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') || die();

ExtensionUtility::configurePlugin(
    'NetXFal',
    'ClearCache',
    [Cache::class => 'clearCache'],
    [Cache::class => 'clearCache'],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);
