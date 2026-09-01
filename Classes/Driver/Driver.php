<?php

declare(strict_types=1);

/*
 * This file is part of the "netx_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\NetXFal\Driver;

use Fairway\NetXFal\Client\NetXClient;
use RuntimeException;
use Throwable;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Resource\Capabilities;
use TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\MimeTypeDetector;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Driver extends AbstractHierarchicalFilesystemDriver
{
    public const EXTENSION_KEY = 'netx_fal';
    public const DRIVER_TYPE = 'FairwayNetXDriver';

    public static NetXClient $client;
    protected Logger $log;
    protected int $instance;
    protected Capabilities $capabilities;
    /** @var array<string, mixed> */
    protected array $configuration;
    protected ?int $storageUid = null;
    protected ?ResourceStorage $storage = null;

    /**
     * @param array<string, mixed> $configuration
     */
    public function __construct(array $configuration = [])
    {
        parent::__construct($configuration);

        $this->configuration = $configuration;
        $this->instance = random_int(0, mt_getrandmax());
        $this->log = GeneralUtility::makeInstance(LogManager::class)->getLogger(self::class);
        $this->log->debug("$this->instance: __construct(" . json_encode($configuration) . ')');
        $this->capabilities = GeneralUtility::makeInstance(
            Capabilities::class,
            Capabilities::CAPABILITY_BROWSABLE |
            Capabilities::CAPABILITY_PUBLIC |
            Capabilities::CAPABILITY_HIERARCHICAL_IDENTIFIERS
        );
    }

    /**
     * Processes the configuration for this driver.
     */
    public function processConfiguration(): void
    {
        //$this->log->debug("$this->instance: processConfiguration()");
    }

    /**
     * Initializes this object. This is called by the storage after the driver
     * has been attached.
     */
    public function initialize(): void
    {
        self::$client = new NetXClient($this->configuration, $this->storageUid);
    }

    /**
     * Merges the capabilities merged by the user at the storage
     * configuration into the actual capabilities of the driver
     * and returns the result.
     */
    public function mergeConfigurationCapabilities(Capabilities $capabilities): Capabilities
    {
        $this->capabilities->and($capabilities);
        return $this->capabilities;
    }

    /**
     * Returns the identifier of the root level folder of the storage.
     */
    public function getRootLevelFolder(): string
    {
        //$this->log->debug("$this->instance: getRootLevelFolder(): " . self::ROOT_FOLDER_IDENTIFIER);
        return '/';
    }

    /**
     * Returns the identifier of the default folder new files should be put into.
     */
    public function getDefaultFolder(): string
    {
        $ret = $this->getRootLevelFolder();
        $this->log->debug("$this->instance: getDefaultFolder(): $ret");
        return $ret;
    }

    public function setStorage(ResourceStorage $storage): void
    {
        $this->storage = $storage;
    }

    /**
     * Returns the public URL to a file.
     * Either fully qualified URL or relative to PATH_site (rawurlencoded).
     */
    public function getPublicUrl(string $identifier): ?string
    {
        //Storage not yet set → no PublicUrl possible
        if ($this->storage === null) {
            return null;
        }
        try {
            $file = $this->storage->getFile($identifier);
            if ($file instanceof ProcessedFile) {
                return $file->getPublicUrl();
            }
        } catch (Throwable) {
            return null;
        }
        return null;
    }

    /**
     * Creates a folder, within a parent folder.
     * If no parent folder is given, a root level folder will be created
     *
     * @throws Exception
     */
    public function createFolder(string $newFolderName, string $parentFolderIdentifier = '', bool $recursive = false): string
    {
        throw new Exception('Storage is read-only.');
    }

    /**
     * Renames a folder in this storage.
     *
     * @throws Exception
     */
    public function renameFolder(string $folderIdentifier, string $newName): array
    {
        throw new Exception('Storage is read-only.');
    }

    /**
     * Removes a folder in filesystem.
     *
     * @throws Exception
     */
    public function deleteFolder(string $folderIdentifier, bool $deleteRecursively = false): bool
    {
        throw new Exception('Storage is read-only.');
    }

    /**
     * Checks if a file exists.
     */
    public function fileExists(string $fileIdentifier): bool
    {
        if ($this->folderExists($fileIdentifier)) {
            $this->log->debug("$this->instance: fileExists($fileIdentifier): false");
            return false;
        }

        $fileInfo = self::$client->getFileInfo($fileIdentifier)['info'] ?? null;
        $ret = ((!str_ends_with($fileIdentifier, '/')) and is_array($fileInfo) and $fileInfo !== []);
        $this->log->debug("$this->instance: fileExists($fileIdentifier): " . ($ret ? 'true' : 'false'));
        return $ret;
    }

    /**
     * Checks if a folder exists.
     */
    public function folderExists(string $folderIdentifier): bool
    {
        $folderIdentifier = rtrim($folderIdentifier, '/\\') . '/';
        if ($folderIdentifier === '/') {
            $this->log->debug("$this->instance: folderExists($folderIdentifier): true");
            return true;
        }

        $folderInfo = $this->getFolderInfoByIdentifier($folderIdentifier);
        $ret = $folderInfo !== [];
        $this->log->debug("$this->instance: folderExists($folderIdentifier): " . ($ret ? 'true' : 'false'));
        return $ret;
    }

    /**
     * Checks if a folder contains files and (if supported) other folders.
     */
    public function isFolderEmpty(string $folderIdentifier): bool
    {
        $ret = $this->countFoldersInFolder($folderIdentifier) + $this->countFilesInFolder($folderIdentifier) == 0;
        $this->log->debug("$this->instance: isFolderEmpty($folderIdentifier): " . ($ret ? 'true' : 'false'));
        return $ret;
    }

    /**
     * Adds a file from the local server hard disk to a given path in TYPO3s
     * virtual file system. This assumes that the local file exists, so no
     * further check is done here! After a successful the original file must
     * not exist anymore.
     *
     * @throws Exception
     */
    public function addFile(string $localFilePath, string $targetFolderIdentifier, string $newFileName = '', bool $removeOriginal = true): string
    {
        throw new Exception('Storage is read-only.');
    }

    /**
     * Creates a new (empty) file and returns the identifier.
     *
     * @throws Exception
     */
    public function createFile(string $fileName, string $parentFolderIdentifier): string
    {
        throw new Exception('Storage is read-only.');
    }

    /**
     * Copies a file *within* the current storage.
     * Note that this is only about an inner storage copy action,
     * where a file is just copied to another folder in the same storage.
     *
     * @throws Exception
     */
    public function copyFileWithinStorage(string $fileIdentifier, string $targetFolderIdentifier, string $fileName): string
    {
        throw new Exception('Storage is read-only.');
    }

    /**
     * Renames a file in this storage.
     *
     * @throws Exception
     */
    public function renameFile(string $fileIdentifier, string $newName): string
    {
        throw new Exception('Storage is read-only.');
    }

    /**
     * Replaces a file with file in local file system.
     *
     * @param string $fileIdentifier
     * @param string $localFilePath
     * @return bool TRUE if the operation succeeded
     * @throws Exception
     */
    public function replaceFile(string $fileIdentifier, string $localFilePath): bool
    {
        throw new Exception('Storage is read-only.');
    }

    /**
     * Removes a file from the filesystem. This does not check if the file is
     * still used or if it is a bad idea to delete it for some other reason
     * this has to be taken care of in the upper layers (e.g. the Storage)!
     *
     * @throws Exception
     */
    public function deleteFile(string $fileIdentifier): bool
    {
        //$this->log->debug("$this->instance: deleteFile($fileIdentifier)");
        throw new Exception('Storage is read-only.');
    }

    /**
     * Creates a hash for a file.
     */
    public function hash(string $fileIdentifier, string $hashAlgorithm): string
    {
        $ret = $this->hashIdentifier($fileIdentifier);
        $this->log->debug("$this->instance: hash($fileIdentifier, $hashAlgorithm): $ret");
        return $ret;
    }

    /**
     * Moves a file *within* the current storage.
     * Note that this is only about an inner-storage move action,
     * where a file is just moved to another folder in the same storage.
     *
     * @throws Exception
     */
    public function moveFileWithinStorage(string $fileIdentifier, string $targetFolderIdentifier, string $newFileName): string
    {
        throw new Exception('Storage is read-only.');
    }

    /**
     * Folder equivalent to moveFileWithinStorage().
     *
     * @param string $sourceFolderIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFolderName
     * @return array<string, string> All files which are affected, map of old => new file identifiers
     * @throws Exception
     */
    public function moveFolderWithinStorage(string $sourceFolderIdentifier, string $targetFolderIdentifier, string $newFolderName): array
    {
        throw new Exception('Storage is read-only.');
    }

    /**
     * Folder equivalent to copyFileWithinStorage().
     *
     * @throws Exception
     */
    public function copyFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName): bool
    {
        throw new Exception('Storage is read-only.');
    }

    /**
     * Returns the contents of a file. Beware that this requires to load the
     * complete file into memory and also may require fetching the file from an
     * external location. So this might be an expensive operation (both in terms
     * of processing resources and money) for large files.
     */
    public function getFileContents(string $fileIdentifier): string
    {
        return self::$client->getFileContents($fileIdentifier);
    }

    /**
     * Sets the contents of a file to the specified value.
     *
     * @throws Exception
     */
    public function setFileContents(string $fileIdentifier, string $contents): int
    {
        //$this->log->debug("$this->instance: setFileContents($fileIdentifier, $contents)");
        throw new Exception('Storage is read-only.');
    }

    /**
     * Checks if a file inside a folder exists
     *
     * @throws Exception
     */
    public function fileExistsInFolder(string $fileName, string $folderIdentifier): bool
    {
        return array_key_exists($fileName, self::$client->getFolderInfo($folderIdentifier, 'filename'));
    }

    /**
     * Checks if a folder inside a folder exists.
     *
     * @throws Exception
     */
    public function folderExistsInFolder(string $folderName, string $folderIdentifier): bool
    {
        //$this->log->debug("$this->instance: folderExistsInFolder($folderName, $folderIdentifier)");
        return array_key_exists($folderName, self::$client->getFolderInfo($folderIdentifier, 'foldername'));
    }

    /**
     * Returns a path to a local copy of a file for processing it. When changing the
     * file, you have to take care of replacing the current version yourself!
     */
    public function getFileForLocalProcessing(string $fileIdentifier, bool $writable = true): string
    {
        $fileInfo = self::$client->getFileInfo($fileIdentifier);
        $extension = $fileInfo['info']['extension'] ?? 'bin';

        // Temp-Datei anlegen
        $tmpFile = GeneralUtility::tempnam('fal-tempfile-', '.' . $extension);

        $streamContext = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header'  => [
                    self::$client->createAuthenticationHeader(),
                    'Accept-Encoding: gzip, deflate',
                ],
                'max_redirects'    => 10,
                'protocol_version' => 1.1,
                'timeout'          => 10,
                'ignore_errors'    => true,
                'ssl' => [
                    'verify_peer'      => true,
                    'verify_peer_name' => true,
                ],
            ],
        ]);

        $remoteStream = fopen(self::$client->getUrl($fileIdentifier), 'r', false, $streamContext);
        if (!$remoteStream) {
            throw new RuntimeException('Could not open remote stream for ' . $fileIdentifier);
        }

        $localStream = fopen($tmpFile, 'w+b');
        if (!$localStream) {
            fclose($remoteStream);
            throw new RuntimeException('Could not open local temp file ' . $tmpFile);
        }

        // Copy remote → local
        stream_copy_to_stream($remoteStream, $localStream);

        fclose($remoteStream);
        fclose($localStream);

        return $tmpFile;
    }

    /**
     * Returns the permissions of a file/folder as an array
     * (keys r, w) of boolean flags
     */
    public function getPermissions(string $identifier): array
    {
        return ['r' => true, 'w' => false];
    }

    /**
     * Directly output the contents of the file to the output
     * buffer. Should not take care of header files or flushing
     * buffer before. Will be taken care of by the Storage.
     *
     * NetX file URLs are protected API endpoints. The stream context must send
     * the API token authorization header and keep HTTP errors readable so TYPO3
     * can report failed downloads instead of writing invalid output.
     */
    public function dumpFileContents(string $identifier): void
    {
        $streamContext = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header'  => [
                    self::$client->createAuthenticationHeader(),
                    'Accept-Encoding: gzip, deflate',
                ],
                'max_redirects'    => 10,
                'protocol_version' => 1.1,
                'timeout'          => 10,
                'ignore_errors'    => true,
                'ssl' => [
                    'verify_peer'      => true,
                    'verify_peer_name' => true,
                ],
            ],
        ]);

        $contents = file_get_contents(self::$client->getUrl($identifier), false, $streamContext);
        if ($contents === false) {
            throw new RuntimeException('Could not fetch remote file for ' . $identifier);
        }
        $statusLine = $http_response_header[0] ?? '';
        if (preg_match('/^HTTP\/\S+\s+([1-5][0-9]{2})\b/', $statusLine, $matches) === 1 && (int)$matches[1] >= 400) {
            throw new RuntimeException(sprintf('Could not fetch remote file for %s: HTTP %s', $identifier, $matches[1]));
        }

        $handle = fopen('php://output', 'w');
        if ($handle === false) {
            throw new RuntimeException('Could not open output stream.');
        }
        fwrite($handle, $contents);
        fclose($handle);
    }

    /**
     * Checks if a given identifier is within a container, e.g. if
     * a file or folder is within another folder.
     * This can e.g. be used to check for web-mounts.
     *
     * Hint: this also needs to return TRUE if the given identifier
     * matches the container identifier to allow access to the root
     * folder of a filemount.
     */
    public function isWithin(string $folderIdentifier, string $identifier): bool
    {
        $folderIdentifier = rtrim($folderIdentifier, '/\\') . '/';
        $id = rtrim($identifier, '/\\') . '/';
        $ret = ($identifier and (str_starts_with($id, $folderIdentifier)));
        $this->log->debug("$this->instance: isWithin($folderIdentifier, $identifier): " . ($ret ? 'true' : 'false'));
        return $ret;
    }

    /**
     * Returns information about a file.
     */
    public function getFileInfoByIdentifier(string $fileIdentifier, array $propertiesToExtract = []): array
    {
        $ret = self::$client->getFileInfo($fileIdentifier)['info'] ?? [];
        if ($ret !== []) {
            $ret = $this->normalizeFileInfo($ret, $fileIdentifier);
        }
        $this->log->debug("$this->instance: getFileInfoByIdentifier($fileIdentifier, " . json_encode($propertiesToExtract) . '): ' . json_encode($ret));
        return $ret;
    }

    /**
     * @param array<string, mixed> $fileInfo
     * @return array<string, mixed>
     */
    private function normalizeFileInfo(array $fileInfo, string $fileIdentifier): array
    {
        $fileInfo['identifier'] ??= $fileIdentifier;
        $fileInfo['identifier'] = (string)$fileInfo['identifier'];
        $fileInfo['name'] ??= basename($fileIdentifier);
        $fileInfo['extension'] ??= pathinfo($fileInfo['name'], PATHINFO_EXTENSION);

        if (!isset($fileInfo['mime_type']) || !is_string($fileInfo['mime_type']) || trim($fileInfo['mime_type']) === '') {
            $fileInfo['mime_type'] = $this->getMimeTypeFromExtension((string)$fileInfo['extension']);
        }

        return $fileInfo;
    }

    private function getMimeTypeFromExtension(string $extension): string
    {
        $extension = ltrim(strtolower($extension), '.');
        if ($extension !== '') {
            $mimeTypes = GeneralUtility::makeInstance(MimeTypeDetector::class)->getMimeTypesForFileExtension($extension);
            if ($mimeTypes !== []) {
                return $mimeTypes[0];
            }
        }

        return 'application/octet-stream';
    }

    /**
     * Returns information about a file.
     *
     * @return array<string, mixed>
     */
    public function getFolderInfoByIdentifier(string $folderIdentifier): array
    {
        $folderIdentifier = rtrim($folderIdentifier, '/\\') . '/';
        try {
            $ret = self::$client->getFolderInfo($folderIdentifier)['info'] ?? [];
        } catch (Throwable) {
            $ret = [];
        }
        //$this->log->debug("$this->instance: getFolderInfoByIdentifier($folderIdentifier): " . json_encode($ret));
        return $ret;
    }

    /**
     * Returns the identifier of a file inside the folder
     *
     * @throws Exception
     */
    public function getFileInFolder(string $fileName, string $folderIdentifier): string
    {
        //$this->log->debug("$this->instance: getFileInFolder($fileName, $folderIdentifier)");
        return self::$client->getFolderInfo($folderIdentifier, 'filename')[$fileName];
    }

    /**
     * Returns a list of files inside the specified path
     */
    public function getFilesInFolder(
        string $folderIdentifier,
        int $start = 0,
        int $numberOfItems = 0,
        bool $recursive = false,
        array $filenameFilterCallbacks = [],
        string $sort = '',
        bool $sortRev = false
    ): array {
        $folderIdentifier = rtrim($folderIdentifier, '/\\') . '/';
        if ($recursive or (($sort != 'name') and ($sort != 'fileext') and ($sort != 'size') and ($sort != 'tstamp'))) {
            $ret = self::$client->getFolderInfo($folderIdentifier, 'assets');
            if ($recursive) {
                $folders = $this->getFoldersInFolder($folderIdentifier, 0, 0, true);
                foreach ($folders as $folder) {
                    $ret = array_merge($ret, self::$client->getFolderInfo($folder, 'assets'));
                }
            } elseif ($sortRev) {
                $ret = array_reverse($ret);
            }
        } else {
            $data = self::$client->getFolderInfo($folderIdentifier, 'file');
            usort($data, function ($a, $b) use ($sortRev, $sort) {
                if ($sort == 'fileext') {
                    $a = $a['extension'];
                    $b = $b['extension'];
                    return $sortRev ? strnatcmp($b, $a) : strnatcmp($a, $b);
                }
                if ($sort == 'tstamp') {
                    $a = $a['info']['ctime'];
                    $b = $b['info']['ctime'];
                    return $sortRev ? $b <=> $a : $a <=> $b;
                }
                if ($sort == 'name') {
                    $a = $a['info']['name'];
                    $b = $b['info']['name'];
                    return $sortRev ? strnatcmp($b, $a) : strnatcmp($a, $b);
                }   // size
                $a = $a['info']['size'];
                $b = $b['info']['size'];
                return $sortRev ? $b <=> $a : $a <=> $b;

            });
            $ret = [];
            foreach ($data as $d) {
                $ret[] = $d['info']['identifier'];
            }
        }
        if (($start > 0) or ($numberOfItems > 0)) {
            $ret = array_slice($ret, $start, $numberOfItems > 0 ? $numberOfItems : null);
        }
        //$this->log->debug("$this->instance: getFilesInFolder($folderIdentifier, $start, $numberOfItems, $recursive, " . json_encode($filenameFilterCallbacks) . ", $sort, $sortRev): " . json_encode($ret));
        return $ret;
    }

    /**
     * Returns the identifier of a folder inside the folder
     * @throws Exception
     */
    public function getFolderInFolder(string $folderName, string $folderIdentifier): string
    {
        //$this->log->debug("$this->instance: getFolderInFolder($folderName, $folderIdentifier)");
        return self::$client->getFolderInfo($folderIdentifier, 'foldername')[$folderName];
    }

    /**
     * Returns a list of folders inside the specified path
     */
    public function getFoldersInFolder(
        string $folderIdentifier,
        int $start = 0,
        int $numberOfItems = 0,
        bool $recursive = false,
        array $folderNameFilterCallbacks = [],
        string $sort = '',
        bool $sortRev = false
    ): array {
        $folderIdentifier = rtrim($folderIdentifier, '/\\') . '/';
        if ($recursive) {
            $ret = self::$client->getFolderInfo($folderIdentifier, 'children');
            $tmp = $ret;
            foreach ($tmp as $folder) {
                $ret = array_merge($ret, $this->getFoldersInFolder($folder, 0, 0, true));
            }
        } elseif ($sort != 'name') {
            $ret = self::$client->getFolderInfo($folderIdentifier, 'children');
            if ($sortRev) {
                $ret = array_reverse($ret);
            }
        } else {
            $data = self::$client->getFolderInfo($folderIdentifier, 'folder');
            usort($data, function ($a, $b) use ($sortRev) {
                $a = $a['name'];
                $b = $b['name'];
                return $sortRev ? strnatcmp($b, $a) : strnatcmp($a, $b);
            });
            $ret = [];
            foreach ($data as $d) {
                $ret[] = $d['identifier'];
            }
        }
        if (($start > 0) or ($numberOfItems > 0)) {
            $ret = array_slice($ret, $start, $numberOfItems > 0 ? $numberOfItems : null);
        }
        //$this->log->debug("$this->instance: getFoldersInFolder($folderIdentifier, $start, $numberOfItems, $recursive, " . json_encode($folderNameFilterCallbacks) . "$sort, $sortRev): " . json_encode($ret));
        return $ret;
    }

    /**
     * Returns the number of files inside the specified path
     */
    public function countFilesInFolder(string $folderIdentifier, bool $recursive = false, array $filenameFilterCallbacks = []): int
    {
        $ret = count($this->getFilesInFolder($folderIdentifier, 0, 0, $recursive, $filenameFilterCallbacks));
        $this->log->debug("$this->instance: countFilesInFolder($folderIdentifier, $recursive, " . json_encode($filenameFilterCallbacks) . "): $ret");
        return $ret;
    }

    /**
     * Returns the number of folders inside the specified path
     */
    public function countFoldersInFolder(string $folderIdentifier, bool $recursive = false, array $folderNameFilterCallbacks = []): int
    {
        $ret = count($this->getFoldersInFolder($folderIdentifier, 0, 0, $recursive, $folderNameFilterCallbacks));
        $this->log->debug("$this->instance: countFoldersInFolder($folderIdentifier, $recursive, " . json_encode($folderNameFilterCallbacks) . "): $ret");
        return $ret;
    }

    /**
     * Sets the storage uid the driver belongs to
     *
     * @param int $storageUid
     */
    public function setStorageUid(int $storageUid): void
    {
        $this->storageUid = $storageUid;
    }

    /**
     * Returns the capabilities of this driver.
     *
     * @return Capabilities
     */
    public function getCapabilities(): Capabilities
    {
        return $this->capabilities;
    }

    /**
     * Returns TRUE if this driver has the given capability.
     *
     * @param int $capability A capability, as defined in a CAPABILITY_* constant
     * @return bool
     */
    public function hasCapability(int $capability): bool
    {
        if (!in_array($capability, [
            Capabilities::CAPABILITY_BROWSABLE,
            Capabilities::CAPABILITY_PUBLIC,
            Capabilities::CAPABILITY_WRITABLE,
            Capabilities::CAPABILITY_HIERARCHICAL_IDENTIFIERS,
        ], true)) {
            return false;
        }
        return $this->capabilities->hasCapability($capability);
    }

    /**
     * Returns TRUE if this driver uses case-sensitive identifiers. NOTE: This
     * is a configurable setting, but the setting does not change the way the
     * underlying file system treats the identifiers; the setting should
     * therefore always reflect the file system and not try to change its
     * behaviour
     *
     * @return bool
     */
    public function isCaseSensitiveFileSystem(): bool
    {
        return true;
    }

    /**
     * Cleans a fileName from not allowed characters
     *
     * @param string $fileName
     * @param string $charset Charset of the a fileName
     *                        (defaults to current charset; depending on context)
     * @return string the cleaned filename
     */
    public function sanitizeFileName($fileName, $charset = ''): string
    {
        return $fileName;
    }

    /**
     * Hashes a file identifier, taking the case sensitivity of the file system
     * into account. This helps mitigating problems with case-insensitive
     * databases.
     */
    public function hashIdentifier(string $identifier): string
    {
        $ret = sha1($identifier);
        $this->log->debug("$this->instance: hashIdentifier($identifier): $ret");
        return $ret;
    }

    /**
     * Returns the identifier of the folder the file resides in
     */
    public function getParentFolderIdentifierOfIdentifier(string $fileIdentifier): string
    {
        $ret = rtrim(dirname($fileIdentifier), '/\\') . '/';
        $this->log->debug("$this->instance: getParentFolderIdentifierOfIdentifier($fileIdentifier): $ret");
        return $ret;
    }

}
