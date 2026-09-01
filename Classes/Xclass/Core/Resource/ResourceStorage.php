<?php

declare(strict_types=1);

/*
 * This file is part of the "netx_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\NetXFal\Xclass\Core\Resource;

use Fairway\NetXFal\Driver\Driver;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceStorage as CoreResourceStorage;

class ResourceStorage extends CoreResourceStorage
{
    /**
     * @param mixed $action
     * @param FileInterface $file
     */
    public function checkFileActionPermission($action, $file): bool
    {
        if ($file->getStorage()->getDriverType() !== Driver::DRIVER_TYPE) {
            return parent::checkFileActionPermission($action, $file);
        }

        return $this->checkNetXFileActionPermission($action, $file);
    }

    public function checkFolderActionPermission($action, ?Folder $folder = null)
    {
        $driverType = $folder?->getStorage()->getDriverType() ?? $this->getDriverType();
        if ($driverType !== Driver::DRIVER_TYPE) {
            return parent::checkFolderActionPermission($action, $folder);
        }

        return $this->checkNetXFolderActionPermission($action, $folder);
    }

    /**
     * @param mixed $action
     */
    public function checkNetXFileActionPermission($action, FileInterface $file): bool
    {
        if ($this->checkUserNetXActionPermission($action, 'File') === false) {
            return false;
        }
        if (!$this->checkValidFileExtension($file)) {
            return false;
        }

        $isReadCheck = in_array($action, ['read', 'copy'], true);
        $isWriteCheck = in_array($action, ['add', 'write', 'move', 'rename', 'replace', 'delete', 'editMeta'], true);

        if (!$this->isWithinFileMountBoundaries($file, $isWriteCheck)) {
            return false;
        }

        $isMissing = false;
        if ($file instanceof File) {
            $isMissing = $file->isMissing();
        }

        if ($this->driver->fileExists($file->getIdentifier()) === false) {
            if ($file instanceof File) {
                $file->setMissing(true);
            }
            $isMissing = true;
        }

        if ($isWriteCheck && ($isMissing || !$this->isWritable())) {
            return false;
        }

        if (!$isMissing) {
            $filePermissions = $this->driver->getPermissions($file->getIdentifier());
            if ($isReadCheck && !$filePermissions['r']) {
                return false;
            }
            if ($isWriteCheck && !$filePermissions['w']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param mixed $action
     */
    public function checkNetXFolderActionPermission($action, ?Folder $folder = null): bool
    {
        if ($this->checkUserNetXActionPermission($action, 'Folder') === false) {
            return false;
        }

        if ($folder === null) {
            return true;
        }

        $isReadCheck = in_array($action, ['read', 'copy'], true);
        $isWriteCheck = in_array($action, ['add', 'move', 'write', 'delete', 'rename'], true);

        if (!$this->isWithinFileMountBoundaries($folder, $isWriteCheck)) {
            return false;
        }
        if ($isReadCheck && !$this->isBrowsable()) {
            return false;
        }
        if ($isWriteCheck && !$this->isWritable()) {
            return false;
        }

        $folderPermissions = $this->driver->getPermissions($folder->getIdentifier());
        if ($isReadCheck && !$folderPermissions['r']) {
            return false;
        }
        if ($isWriteCheck && !$folderPermissions['w']) {
            return false;
        }

        return true;
    }

    /**
     * @param mixed $action
     */
    public function checkUserNetXActionPermission($action, string $type): bool
    {
        if (!$this->evaluatePermissions) {
            return true;
        }

        return !empty($this->userPermissions['netx_' . strtolower($type) . '_permission_' . strtolower((string)$action)]);
    }
}
