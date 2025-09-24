<?php

namespace Fairway\NetXFal\Processor;

use Fairway\NetXFal\Utility\DriverUtility;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Imaging\Exception\ZeroImageDimensionException;
use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
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
        $isImage = $task->getType();
        $isBackend = ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend();
        $inArray = in_array($task->getName(), ['Preview', 'CropScaleMask'], true);
        //$fileProsseing = (!$context->hasAspect('fileProcessing') || $context->getPropertyFromAspect('fileProcessing', 'deferProcessing'));
        $hasFile = !$task->getSourceFile()->getStorage()->getProcessingFolder()->hasFile($task->getTargetFileName());
        return ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()
            && $task->getType() === 'Image'
            && in_array($task->getName(), ['Preview', 'CropScaleMask'], true)
            //&& (!$context->hasAspect('fileProcessing') || $context->getPropertyFromAspect('fileProcessing', 'deferProcessing'))
            && !$task->getSourceFile()->getStorage()->getProcessingFolder()->hasFile($task->getTargetFileName());
    }

    public function processTask(TaskInterface $task): void
    {
        $this->log->debug('processTask');

        $driverClient = DriverUtility::getClient();

        try {
            $imageDimension = ImageDimension::fromProcessingTask($task);
        } catch (ZeroImageDimensionException $e) {
            $imageDimension = new ImageDimension(64, 64);
            $id = $task->getSourceFile()->getIdentifier();
            $info = $driverClient->getFileInfo($id);
            if($info) {
                $width = $info['info']['width'];
                $height = $info['info']['height'];
                //            if ($width and $height) {
                //                $max = 250;
                //                if (($width > $max) or ($height > $max)) {
                //                    if ($width > $height) {
                //                        $height = intval($height * $max / $width);
                //                        $width = $max;
                //                    } else {
                //                        $width = intval($width * $max / $height);
                //                        $height = $max;
                //                    }
                //                }
                //            } else {
                //                $width = 0;
                //                $height = 0;
                //            }
            }
        }

        $processedFile = $task->getTargetFile();
        if (!$processedFile->isPersisted()) {
            $processedFileRepository = GeneralUtility::makeInstance(ProcessedFileRepository::class);
            $processedFileRepository->add($processedFile);
        }
        $processedFile->setName($task->getTargetFileName());
        $processingUrl = (string)GeneralUtility::makeInstance(UriBuilder::class)
            ->buildUriFromRoute(
                'image_processing',
                [
                    'id' => $processedFile->getUid(),
                ]
            );

        $processedFile->updateProcessingUrl(GeneralUtility::locationHeaderUrl($processingUrl));
        $processedFile->updateProperties(
            [
                'width' => $width,
                'height' => $height,
                'size' => 0,
                'checksum' => $task->getConfigurationChecksum(),
            ]
        );
        $this->updateFileMetadata($task->getTargetFile()->getUid(), $width, $height);
        $task->setExecuted(true);

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
