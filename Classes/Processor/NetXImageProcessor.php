<?php

declare(strict_types=1);

/*
 * This file is part of the "netx_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\NetXFal\Processor;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Imaging\Exception\ZeroImageDimensionException;
use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Resource\Processing\ProcessorInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class NetXImageProcessor implements ProcessorInterface
{
    protected Logger $log;

    public function canProcessTask(TaskInterface $task): bool
    {
        $this->log = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        $context = GeneralUtility::makeInstance(Context::class);
        return ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()
            && $task->getType() === 'Image'
            && in_array($task->getName(), ['Preview', 'CropScaleMask'], true)
            //&& (!$context->hasAspect('fileProcessing') || $context->getPropertyFromAspect('fileProcessing', 'deferProcessing'))
            && !$task->getSourceFile()->getStorage()->getProcessingFolder()->hasFile($task->getTargetFileName());
    }

    public function processTask(TaskInterface $task): void
    {
        $this->log->debug('processTask', [
            'name'   => $task->getName(),
            'config' => $task->getConfiguration(),
        ]);

        // Load originial file from system
        $driverClient     = \Fairway\NetXFal\Utility\DriverUtility::getClient();
        $sourceIdentifier = $task->getSourceFile()->getIdentifier();
        $originalContent  = $driverClient->getFileContents($sourceIdentifier);
        if (!$originalContent) {
            throw new \RuntimeException('Empty content from external client for ' . $sourceIdentifier);
        }

        $imgInfo    = @getimagesizefromstring($originalContent);
        $mime       = $imgInfo['mime'] ?? 'image/jpeg';
        $origWidth  = (int)($imgInfo[0] ?? 0);
        $origHeight = (int)($imgInfo[1] ?? 0);

        $src = @imagecreatefromstring($originalContent);
        if (!$src || !$origWidth || !$origHeight) {
            throw new \RuntimeException('GD could not create image from source');
        }

        // 2) Crop over typo3 api
        $config   = $task->getConfiguration();
        $cropJson = (string)($config['crop'] ?? '');
        $variant  = $config['cropVariant'] ?? 'default';

        // Determine area and convert to absolute pixels
        $area = CropVariantCollection::create($cropJson)->getCropArea($variant);
        $rect = $area->makeAbsoluteBasedOnFile($task->getSourceFile());

        $cropX = (int)round($rect->getOffsetLeft());
        $cropY = (int)round($rect->getOffsetTop());
        $cropW = (int)round($rect->getWidth());
        $cropH = (int)round($rect->getHeight());

        // Fallback & Clamping
        if ($rect->isEmpty() || $cropW <= 0 || $cropH <= 0) {
            $cropX = 0;
            $cropY = 0;
            $cropW = $origWidth;
            $cropH = $origHeight;
        } else {
            if ($cropX < 0) {
                $cropX = 0;
            }
            if ($cropY < 0) {
                $cropY = 0;
            }
            if ($cropX + $cropW > $origWidth) {
                $cropW = $origWidth  - $cropX;
            }
            if ($cropY + $cropH > $origHeight) {
                $cropH = $origHeight - $cropY;
            }
        }

        // Determine target measurements *as for the core*
        // width/height/maxWidth/maxHeight/Preview/CropScaleMask etc.

        try {
            // Use core logic (like the original processor)
            $dim = ImageDimension::fromProcessingTask($task);
            $targetWidth  = (int)$dim->getWidth();
            $targetHeight = (int)$dim->getHeight();

            // Defensive: if for any reason the result is 0, fallback
            if ($targetWidth <= 0 || $targetHeight <= 0) {
                throw new ZeroImageDimensionException('Computed target size is zero', 1710000000);
            }

        } catch (ZeroImageDimensionException $e) {
            $cfgW  = (int)($task->getConfiguration()['width']    ?? 0);
            $cfgH  = (int)($task->getConfiguration()['height']   ?? 0);
            $maxW  = (int)($task->getConfiguration()['maxWidth'] ?? 0);
            $maxH  = (int)($task->getConfiguration()['maxHeight'] ?? 0);

            // Target box: width/height preferred, otherwise maxWidth/maxHeight, otherwise crop size
            $boxW = $cfgW ?: $maxW ?: $cropW;
            $boxH = $cfgH ?: $maxH ?: $cropH;

            // fit proportionally (contain)
            $scale = min(
                ($boxW > 0 ? $boxW / $cropW : 1),
                ($boxH > 0 ? $boxH / $cropH : 1)
            );
            if (!is_finite($scale) || $scale <= 0) {
                $scale = 1;
            }

            $targetWidth  = max(1, (int)round($cropW * $scale));
            $targetHeight = max(1, (int)round($cropH * $scale));
        }

        // Create target canvas (transparency for PNG/GIF/WebP)
        $processedFile = $task->getTargetFile();
        $targetExt     = strtolower(pathinfo($processedFile->getName(), PATHINFO_EXTENSION) ?: '');
        if ($targetExt === '') {
            $targetExt = match ($mime) {
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
                default      => 'jpg',
            };
        }

        $dst = imagecreatetruecolor($targetWidth, $targetHeight);
        if (in_array($targetExt, ['png', 'gif', 'webp'], true)) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $targetWidth, $targetHeight, $transparent);
        }

        // Core-compliant: first crop, then scale to the size calculated by the core
        if (!imagecopyresampled(
            $dst,
            $src,
            0,
            0,
            $cropX,
            $cropY,
            $targetWidth,
            $targetHeight,
            $cropW,
            $cropH
        )) {
            imagedestroy($src);
            imagedestroy($dst);
            throw new \RuntimeException('imagecopyresampled failed');
        }

        // Write & place in the expected destination path
        $tmp = GeneralUtility::tempnam('netx-proc-', '.' . $targetExt);
        switch ($targetExt) {
            case 'png':  imagepng($dst, $tmp);
                break;
            case 'gif':  imagegif($dst, $tmp);
                break;
            case 'webp':
                if (!function_exists('imagewebp') || !imagewebp($dst, $tmp, 80)) {
                    $targetExt = 'jpg';
                    $tmp2 = preg_replace('/\.webp$/i', '.jpg', $tmp) ?: ($tmp . '.jpg');
                    imagejpeg($dst, $tmp2, 90);
                    @unlink($tmp);
                    $tmp = $tmp2;
                }
                break;
            default:     imagejpeg($dst, $tmp, 90);
        }
        imagedestroy($src);
        imagedestroy($dst);

        $targetPath = $processedFile->getForLocalProcessing(false);
        GeneralUtility::upload_copy_move($tmp, $targetPath);

        // Set properties & complete task
        $processedFile->updateProperties([
            'width'    => $targetWidth,
            'height'   => $targetHeight,
            'size'     => @filesize($targetPath) ?: 0,
            'checksum' => $task->getConfigurationChecksum(),
        ]);
        $task->setExecuted(true);

        $this->updateFileMetadata($processedFile->getUid(), $origWidth, $origHeight);

        $this->log->debug(sprintf(
            'Processed %s → %s (%dx%d) crop %dx%d@%d,%d',
            $task->getName(),
            $targetPath,
            $targetWidth,
            $targetHeight,
            $cropW,
            $cropH,
            $cropX,
            $cropY
        ));
    }




    protected function updateFileMetadata($fileUid, $width, $height)
    {
        // get original uid
        $originalFileUid = $this->getOriginalFileUid($fileUid);

        // if uid exist
        if ($originalFileUid !== null) {
            try {
                // database connection
                $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
                    ->getQueryBuilderForTable('sys_file_metadata');

                // execute query to update the metadata
                $queryBuilder
                    ->update('sys_file_metadata')
                    ->where(
                        $queryBuilder->expr()->eq('file', $queryBuilder->createNamedParameter($originalFileUid, Connection::PARAM_INT))
                    )
                    ->set('width', $width)
                    ->set('height', $height)
                    ->executeStatement();

                $this->log->debug("Update succesfully: width: $width and height: $height");
            } catch (\Exception $e) {
                $this->log->error('Update failed: Error: ' . $e->getMessage());
            }
        } else {
            $this->log->error("uid not found: $fileUid");
        }
    }

    protected function getOriginalFileUid($processedFileUid)
    {
        // database connection
        $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_processedfile');

        // execute query to select original uid
        $queryBuilder
            ->select('original')
            ->from('sys_file_processedfile')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($processedFileUid, Connection::PARAM_INT))
            );

        // execute query
        $row = $queryBuilder->executeQuery()->fetchAssociative();

        // if exist, return the uid
        return $row['original'] ?? null;
    }

}
