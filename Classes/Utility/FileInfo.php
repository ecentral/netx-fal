<?php

namespace Fairway\NetXFal\Utility;

use Fairway\NetXFal\Utility\MimeTypeSniffer;
use Fairway\NetXFalApi\Models\Asset;
use TYPO3\CMS\Core\Utility\PathUtility;

class FileInfo
{
    private string $identifier;
    private string $identifierHash;
    private string $folderHash;
    private string $name;
    private string $storage;
    private int $fileSize;
    private int $width;
    private int $height;
    private ?string $description = '';
    private ?string $alternative = '';
    private string $mimetype;
    private int $ctime;
    private int $mtime;

    private string $previewUrl;
    private string $thumbUrl;
    private bool|string $publicUrl;
    private string $extension;

    public function __construct(
        Asset $asset,
        string $host,
        string $apiKey,
        int $storage,
        array $configuredInformation = null
    )
    {
        $this->identifier = $asset->getId();
        $this->identifierHash = sha1($this->identifier);
        $this->folderHash = sha1(PathUtility::dirname($this->identifier));

        $mimeTypeSniffer = new MimeTypeSniffer();
        $mimeType = $mimeTypeSniffer->getMimeType($asset->getOriginalUrl($host), $apiKey);
        $this->mimetype = $mimeType;

        $this->storage = $storage;
        $this->fileSize = $asset->getSize();
        $this->mtime = intdiv($asset->getModificationDate(), 1000);
        $this->ctime = intdiv($asset->getCreationDate(), 1000);

        /*$format = (($asset->getCurrentVersion()->getFileCategory() == 'IMAGE') ? $imageFormat :
            (($asset->getCurrentVersion()->getFileCategory() == 'VIDEO') ? $videoFormat : $othersFormat));*/

        $this->initImagesSize($asset);
        $this->initPublicUrl($asset, $host);
        $this->initNameAndExtension($asset);
        $this->initDecriptions($asset, $configuredInformation);
    }

    public function toArray(): array
    {
        return [
            'info' => [
                'identifier' => $this->identifier,
                'identifier_hash' => $this->identifierHash,
                'folder_hash' => $this->folderHash,
                'sha1' => sha1($this->identifier),
                'name' => $this->name,
                'title' => $this->name,
                'storage' => $this->storage,
                'size' => $this->fileSize,
                'width' => $this->width,
                'height' => $this->height,
                'description' => $this->description,
                'alternative' => $this->alternative,
                'extension' => $this->extension,
                'mime_type' => $this->mimetype,
                'creation_date' => $this->ctime,
                'modification_date' => $this->mtime,
            ],
            'preview' => $this->previewUrl,
            'thumbnail' => $this->thumbUrl,
            'publicUrl' => $this->publicUrl,
        ];
    }

    public function initDecriptions(Asset $asset, ?array $configuredInformation)
    {
        if($configuredInformation == null) {
            return;
        }
        foreach($asset->getInformationFieldValueSets() as $key => $valueSet) {
            $informationFieldValueObject = $valueSet->getInformationFieldValues();
            foreach($informationFieldValueObject as $informationFieldValue) {
                if(isset($configuredInformation[$informationFieldValue->getField()])){
                    $informationFieldValue->getField();
                    $informationFieldValue->getValue();
                }

            }
        }
    }

    public function initImagesSize(Asset $asset)
    {
        $width = $asset->getWidth();
        $height = $asset->getHeight();

        if ($width && $height) {
            $max = $this->getMaxSize($this->mimetype);
            if (($max > 0) and (($width > $max) or ($height > $max))) {
                if ($width > $height) {
                    $this->height = intval($height * $max / $width);
                    $this->width = $max;
                } else {
                    $this->width = intval($width * $max / $height);
                    $this->height = $max;
                }
            }
        } else {
            $this->height = 0;
            $this->width = 0;
        }
    }

    private function initPublicUrl(Asset $asset, string $host)
    {
        $this->previewUrl = $asset->getPreviewUrl($host);
        $this->thumbUrl = $asset->getThumbnailUrl($host);
        $this->publicUrl = $asset->getOriginalUrl($host);
    }

    public function initNameAndExtension(Asset $asset){
        $this->name = $asset->getName();
        $this->extension = $asset->getExtension();
        if (substr($this->name, -strlen($this->extension)) !== $this->extension) {
            if (substr($this->name, -1) === '.') {
                $this->name .= substr($this->extension, 1);
            } else {
                $this->name .= '.' . $this->extension;
            }
        }
    }

    // -------- Getter-Methoden --------
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getIdentifierHash(): string
    {
        return $this->identifierHash;
    }

    public function getFolderHash(): string
    {
        return $this->folderHash;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStorage(): string
    {
        return $this->storage;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getAlternative(): ?string
    {
        return $this->alternative;
    }

    public function getMimetype(): string
    {
        return $this->mimetype;
    }

    public function getCtime(): int
    {
        return $this->ctime;
    }

    public function getMtime(): int
    {
        return $this->mtime;
    }

    public function getPreviewUrl(): string
    {
        return $this->previewUrl;
    }

    public function getThumbUrl(): string
    {
        return $this->thumbUrl;
    }

    public function getPublicUrl(): string
    {
        return $this->publicUrl;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function getMaxSize(string $mimeType): int
    {
        $mimeTypePattern = preg_split('/\//', $mimeType);
        $format = $mimeTypePattern[0] ?? 'application';
        return match($format)
        {
            'image' => 3000,
            'text', 'video' => 320,
            'x-conference', 'model', 'message', 'font', 'audio', 'application' => 0,
        };
    }
}
