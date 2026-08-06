<?php

declare(strict_types=1);

/*
 * This file is part of the "netx_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\NetXFal\Client;

use Exception;
use Fairway\NetXFal\Utility\Cache;
use Fairway\NetXFal\Utility\FileInfo;
use Fairway\NetXFal\Utility\RpcClientFolderUtility;
use Fairway\NetXFalApi\Client;
use Fairway\NetXFalApi\Models\Asset;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class NetXClient
{
    public const X_API_KEY_IDENTIFIER = 'Authorization';
    public const X_API_KEY_PREFIX = 'apiToken';

    protected string $host;
    protected string $locale;
    protected string $defaultLocale;
    protected string $username;
    protected string $password;
    protected string $apiKey;

    protected int $storage;
    protected Cache $cache;


    private ?Client $client = null;

    protected Logger $log;

    private int $cacheLifetime;
    private array $roots = [];

    public function __construct(array $config, int $storage)
    {
        try {
            $this->log = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
            $this->log->debug('__construct(' . json_encode($config) . ')');

            $this->initConfiguration($config);

            $this->storage = $storage;

            $this->cache = GeneralUtility::makeInstance(Cache::class);

        } catch (Exception $exception) {
            $this->log->error($exception->getMessage());
        }
    }

    public function createAuthenticationHeader(): string
    {
        return self::X_API_KEY_IDENTIFIER . ': ' . self::X_API_KEY_PREFIX . ' ' . $this->apiKey;

    }
    public function getApiKey(): string
    {
        return $this->apiKey;

    }
    public function initConfiguration(array $configuration): void
    {
        $this->host = $configuration['netxHost'] ?? '';

        $this->apiKey = $configuration['netxApiKey'] ?? '';

        $this->username = $configuration['netxUser'] ?? '';
        $this->password = $configuration['netxPassword'] ?? '';

        $this->locale = $configuration['locale'] ?? 'en';
        $this->defaultLocale = $configuration['defaultLocale'] ?? 'en';


        $this->cacheLifetime = (int)($configuration['cacheLifetimeInMinutes'] ?? 0);
        if ($this->cacheLifetime <= 0 || $this->cacheLifetime >= 30) {
            $this->cacheLifetime = 29;
        }
        $this->cacheLifetime *= 60;

        $roots = trim((string)($configuration['roots'] ?? ''));
        if ($roots === '') {
            $this->roots = [];
            return;
        }

        $this->roots = array_map(
            static fn (string $value): string => '/' . trim($value, '/') . '/',
            array_filter(array_map('trim', explode(',', $roots)), static fn (string $value): bool => $value !== '')
        );
    }

    public function getClient(): Client
    {
        if ($this->client === null) {
            try {
                $this->client = new Client($this->host, $this->apiKey);
            } catch (Exception $exception) {
                $this->log->error($exception->getMessage());
            }
        }
        return $this->client;
    }

    public function extractId(string $identifier): string
    {
        return basename(rtrim($identifier, '/'));
    }

    /**
     * @param array<int, array{locale: string, value: string}> $names
     */
    public function extractName(array $names): ?string
    {
        $default = null;
        foreach ($names as $name) {
            if ($name['locale'] === $this->defaultLocale) {
                $default = $name['value'];
            } elseif ($name['locale'] === $this->locale && $name['value']) {
                return $name['value'];
            }
        }
        return $default;
    }

    protected function initCacheRoot(): void
    {
        $key = '_';
        $rootFolderInfo = [
            'info' => [
                'identifier' => '/',
                'name' => 'NETX',
                'storage' => $this->storage
            ],
            'assets' => [],
            'children' => $this->roots
        ];
        $this->cache->set($key, $rootFolderInfo, [], $this->cacheLifetime);
        $this->cache->set($key . 'file', [], [], $this->cacheLifetime);          // no files in storage root
        $this->cache->set($key . 'filename', [], [], $this->cacheLifetime);      // no files in storage root

        // add DAM nodes as root folders
        $folders = [];
        $foldernames = [];
        foreach ($this->roots as $root) {
            $f = $this->getFolderInfo($root, '');
            $folders[] = ['identifier' => $root, 'name' => $f['info']['name']];
            $foldernames[$f['info']['name']] = $root;
        }
        $this->cache->set($key . 'folder', $folders, [], $this->cacheLifetime);
        $this->cache->set($key . 'foldername', $foldernames, [], $this->cacheLifetime);
        $this->cache->set($key . 'children', $rootFolderInfo['children'], [], $this->cacheLifetime);
        $this->cache->set($key . 'assets', $rootFolderInfo['assets'], [], $this->cacheLifetime);
    }


    private function queryBasicFolderInformation(string $identifier): array
    {
        $folderInfoReturnValue = ['info' => null, 'children' => [], 'assets' => []];

        // node id missing, return empty set
        if ($identifier === '//' || $identifier === './') {
            return $folderInfoReturnValue;
        }

        $folder = $this->getClient()->folderService();
        if ($identifier == '/') {
            $identifier = '/1/';
        }
        $folder = $folder->getFolderById((int)$this->extractId($identifier));

        $folderInfo = RpcClientFolderUtility::getFolderInfoByFolder($folder, $this->storage);

        return $folderInfo;
    }

    private function querySubfolder(string $identifier): array
    {
        $folder = $this->getClient()->folderService()->getFolderById((int)$this->extractId($identifier));
        $folderInfo = RpcClientFolderUtility::getFolderInfoByFolder($folder, $this->storage, $this->locale);

        $foldernames = [];
        $folders = [];
        if ($folder->hasChildFolder()) {
            $childFolders = $this->getClient()->folderService()->getFoldersByParent($folder->getId());
            foreach ($childFolders as $childFolder) {

                $folderInfo['children'][] = $childFolder->getId();
                $foldernames[$childFolder->getName()] = $childFolder->getId();
                $folders[] = ['name' => $childFolder->getName(), 'identifier' => $childFolder->getId()];
            }
        }

        return [$folderInfo, $foldernames, $folders];
    }

    private function querySubfolderAndAssets(string $identifier): array
    {
        $folder = $this->getClient()->folderService()->getFolderById((int)$this->extractId($identifier));
        $folderInfo = RpcClientFolderUtility::getFolderInfoByFolder($folder, $this->storage);

        $foldernames = [];
        $folders = [];
        $filenames = [];
        $files = [];
        $folderInfo['assets'] = [];
        if ($folder->hasChildFolder()) {
            $childFolders = $this->getClient()->folderService()->getFoldersByParent($folder->getId());
            foreach ($childFolders as $childFolder) {

                $folderInfo['children'][] = $childFolder->getId();
                $foldernames[$childFolder->getName()] = $childFolder->getId();
                $folders[] = ['name' => $childFolder->getName(), 'identifier' => $childFolder->getId()];
            }
        }
        if ($folder->hasAssets()) {
            $assets = $this->getClient()->assetService()->getAssetsByFolder($folder->getId());
            foreach ($assets as $asset) {
                $folderInfo['assets'][] = $asset->getId();
                $files[] = $this->toAsset($asset);
                $filenames[$asset->getName()] = $asset->getId();
            }
        }

        return [$folderInfo, $foldernames, $folders, $filenames, $files];
    }

    /**
     *  returns an array of the value selected for extraction or the folder info itself if nothing is specified
     * @param $identifier
     * @param $extract     string 'filename', 'foldername', 'file', 'folder' or ''
     * @return array
     */
    public function getFolderInfo(string $identifier, string $extract = ''): array
    {
        $key = str_replace(['/', '.'], ['_', ''], $identifier);
        if ($identifier === '/' || $identifier === './') {
            $this->initCacheRoot();
        } else {
            if ($extract === '') {
                // we are looking for basic folder information no recursion
                $folderInfo = $this->queryBasicFolderInformation($identifier);
                if (!isset($folderInfo['info'])) {
                    $this->cache->set($key, $folderInfo, [], 60);  // set cache with short ttl to revisit shortly
                    return $folderInfo;
                }
            } elseif ($extract === 'folder' || $extract === 'children') {
                [$folderInfo, $foldernames, $folders] = $this->querySubfolder($identifier);

                if (!isset($folderInfo['info'])) {
                    $this->cache->set($key, $folderInfo, [], 60);  // set cache with short ttl to revisit shortly
                    return $folderInfo;
                }

                $this->cache->set($key . 'folder', $folders, [], $this->cacheLifetime);
                $this->cache->set($key . 'foldername', $foldernames, [], $this->cacheLifetime);
                $this->cache->set($key . 'children', $folderInfo['children'], [], $this->cacheLifetime);

            } else {
                [$folderInfo, $foldernames, $folders, $filenames, $files] = $this->querySubfolderAndAssets($identifier);
                if (!isset($folderInfo['info'])) {
                    $this->cache->set($key, $folderInfo, [], 60);  // set cache with short ttl to revisit shortly
                    return $folderInfo;
                }

                // add additional cache values for further processing
                $this->cache->set($key . 'file', $files, [], $this->cacheLifetime);
                $this->cache->set($key . 'filename', $filenames, [], $this->cacheLifetime);
                $this->cache->set($key . 'folder', $folders, [], $this->cacheLifetime);
                $this->cache->set($key . 'foldername', $foldernames, [], $this->cacheLifetime);

                // fill up cache with asset information
                foreach ($files as $asset) {
                    $assetKey = str_replace('/', '_', $asset['info']['identifier']);
                    $this->cache->set($assetKey, $asset, [], $this->cacheLifetime);
                }

                $this->cache->set($key . 'assets', $folderInfo['assets'], [], $this->cacheLifetime);
                $this->cache->set($key . 'children', $folderInfo['children'], [], $this->cacheLifetime);

            }
            $this->cache->set($key, $folderInfo, [], $this->cacheLifetime);
        }
        $this->log->debug("getFolderInfo($identifier, $extract): " . json_encode($this->cache->get($key . $extract)));

        return $this->cache->get($key . $extract);
    }

    public function getFileInfo(string $identifier): bool|array
    {
        $fileId = $this->getFileIdByFileIdentifier($identifier);
        $key = str_replace('/', '_', $identifier);
        if (!$this->cache->has($key)) {
            try {
                $assets = $this->getClient()->assetService()->getAssets([$fileId]);
                if (count($assets) == 1) {
                    $this->cache->set($key, $this->toAsset($assets[0]), [], $this->cacheLifetime);
                }
            } catch (Exception $exception) {
                $this->log->error("getFileInfo($fileId)" . json_encode($this->cache->get($key)) . ':' . $exception->getMessage());
                $this->cache->set($key, ['info' => null], [], 60); // short cache on error
            }
        }
        $this->log->debug("getFileInfo($fileId)" . json_encode($this->cache->get($key)));
        return $this->cache->get($key);
    }

    private function toAsset(Asset $asset): array
    {
        /*$fileCategory = $asset->getCurrentVersion()->getFileCategory();
        //get download url from original for docuemnts, unknown,
        $originalDownloadUrl = null;
        if ($fileCategory === FileCategory::DOCUMENT ||
            $fileCategory === FileCategory::UNKNOWN ||
            $fileCategory === FileCategory::MODEL3_D ||
            $fileCategory === FileCategory::TEXT ||
            $fileCategory === FileCategory::VIDEO ||
            $fileCategory === FileCategory::PLACEHOLDER) {
            $downloadApi = new DownloadApi($this->getClient(), $this->clientConfiguration);
            $download = $downloadApi->requestDownload((string)$asset->getId(), 1);
            $originalDownloadUrl = $download->getUrl();
        }*/

        $fileInfo = new FileInfo($asset, $this->host, $this->apiKey, $this->storage);

        return $fileInfo->toArray();
    }

    private function getFileIdByFileIdentifier(string $fileIdentifier): int
    {
        $fileArray = explode('/', $fileIdentifier);
        return (int)$fileArray[count($fileArray) - 1];
    }

    public function getUrl(string $identifier, string $type = 'publicUrl')
    {
        if (substr($identifier, 0, 5) === 'thumb') {
            $type = 'thumbnail';
            $identifier = substr($identifier, 5);
        }
        $fileInfo = $this->getFileInfo($identifier);
        $url = $fileInfo[$type];
        $this->log->debug("getUrl($identifier, $type): $url");
        return $url;
    }

    public function getFileContents(string $fileIdentifier): string
    {
        $streamContext = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header' => [
                    self::createAuthenticationHeader(),
                    'Accept-Encoding: gzip, deflate'
                ],
                'max_redirects'    => 10,
                'protocol_version' => 1.1,
                'timeout' => 5,
                'ignore_errors' => true,
                'ssl' => [
                    'verify_peer'      => true,
                    'verify_peer_name' => true,
                ],
            ],
        ]);
        return file_get_contents(self::getUrl($fileIdentifier), false, $streamContext);
    }

}
