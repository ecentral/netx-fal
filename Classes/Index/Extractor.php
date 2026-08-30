<?php

declare(strict_types=1);

/*
 * This file is part of the "netx_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\NetXFal\Index;

use Fairway\NetXFal\Utility\DriverUtility;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\ExtractorInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Extractor implements ExtractorInterface
{
    protected Logger $log;

    public function __construct()
    {
        $this->log = GeneralUtility::makeInstance(LogManager::class)->getLogger(self::class);
    }

    /**
     * Returns an array of supported file types;
     * An empty array indicates all filetypes
     *
     * @return list<string>
     */
    public function getFileTypeRestrictions(): array
    {
        return [];
    }

    /**
     * Get all supported DriverClasses
     *
     * Since some extractors may only work for local files, and other extractors
     * are especially made for grabbing data from remote.
     *
     * Returns array of string with driver names of Drivers which are supported,
     * If the driver did not register a name, it's the classname.
     * empty array indicates no restrictions
     *
     * @return list<string>
     */
    public function getDriverRestrictions(): array
    {
        $driverClass = DriverUtility::getDriver();
        return [$driverClass::DRIVER_TYPE];
    }

    /**
     * Returns the data priority of the extraction Service.
     * Defines the precedence of Data if several extractors
     * extracted the same property.
     *
     * Should be between 1 and 100, 100 is more important than 1
     */
    public function getPriority(): int
    {
        return 50;
    }

    /**
     * Returns the execution priority of the extraction Service
     * Should be between 1 and 100, 100 means runs as first service, 1 runs at last service
     */
    public function getExecutionPriority(): int
    {
        return 50;
    }

    /**
     * Checks if the given file can be processed by this Extractor
     */
    public function canProcess(File $file): bool
    {
        $driverType = DriverUtility::getDriver();

        return $file->getStorage()->getDriverType() === $driverType::DRIVER_TYPE;
    }

    /**
     * The actual processing TASK
     *
     * Should return an array with database properties for sys_file_metadata to write
     *
     * @param array<string, mixed> $previousExtractedData
     * @return array<string, mixed>
     */
    public function extractMetaData(File $file, array $previousExtractedData = []): array
    {
        $this->log->debug('extractMetaData(' . $file->getIdentifier() . ', ' . json_encode($previousExtractedData) . ')');

        return $file->getStorage()->getFileInfoByIdentifier($file->getIdentifier());
    }
}
