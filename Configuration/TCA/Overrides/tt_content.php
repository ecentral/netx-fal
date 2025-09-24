<?php
declare(strict_types = 1);

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
