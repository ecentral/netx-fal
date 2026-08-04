<?php

declare(strict_types=1);

/*
 * This file is part of the "netx_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\NetXFal\Utility;

use Fairway\NetXFalApi\Models\Folder;

class RpcClientFolderUtility
{
    public static function getFolderInfoByFolder(Folder $folder, $storageId, string $locale = ''): array
    {
        return [
            'info' => [
                'identifier' => $folder->getId(),
                'name' => $folder->getName(),
                'storage' => $storageId,
                'mtime' => time(),
            ],
            'children' => [],
            'assets' => []
        ];
    }
}
