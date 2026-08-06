<?php

declare(strict_types=1);

/*
 * This file is part of the "netx_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

defined('TYPO3') || die('Access denied.');
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Resource\Driver\DriverRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

// Driver
$driverClass = (new Typo3Version())->getMajorVersion() < 13
    ? implode('\\', ['Fairway', 'NetXFal', 'Driver', 'DriverV12'])
    : \Fairway\NetXFal\Driver\Driver::class;

$driverRegistry = GeneralUtility::makeInstance(DriverRegistry::class);
$driverRegistry->registerDriverClass(
    $driverClass,
    $driverClass::DRIVER_TYPE,
    'Netx(FAL)',
    'FILE:EXT:' . $driverClass::EXTENSION_KEY . '/Configuration/FlexForm/NetXDriverFlexForm.xml'
);

// Caching
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$driverClass::EXTENSION_KEY] = [
    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
    'groups' => ['system', 'all'],
    'options' => [
        'defaultLifetime' => 29 * 60
    ]
];

// Extractor
$extractorRegistry = new \TYPO3\CMS\Core\Resource\Index\ExtractorRegistry();
$extractorRegistry->registerExtractionService(\Fairway\NetXFal\Index\Extractor::class);

// Processor
if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['processors']['NetXImageProcessor'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['processors']['NetXImageProcessor'] = [
        'className' => \Fairway\NetXFal\Processor\NetXImageProcessor::class,
        'before' => ['LocalImageProcessor'],
    ];
}

// Logging
//$GLOBALS['TYPO3_CONF_VARS']['LOG']['Fairway']['NetXFal']['writerConfiguration'] = [\TYPO3\CMS\Core\Log\LogLevel::DEBUG => [\TYPO3\CMS\Core\Log\Writer\FileWriter::class => []]];
