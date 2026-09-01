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
    /**
     * @return array{info: array{identifier: int|string|null, name: string|null, storage: int, mtime: int}, children: list<int|string>, assets: list<int|string>}
     */
    public static function getFolderInfoByFolder(Folder $folder, int $storageId, string $locale = ''): array
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
