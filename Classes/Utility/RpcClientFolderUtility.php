<?php

namespace Fairway\NetXFal\Utility;

use Fairway\NetXFalApi\Models\Folder;

class RpcClientFolderUtility
{
    public static function getFolderInfoByFolder(Folder $folder, $storageId): array
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
