<?php
/**
 * Created by PhpStorm.
 * User: CMA
 * Date: 05/11/2018
 * Time: 11:18
 */

namespace Fairway\NetXFal\Driver;

use Fairway\NetXFal\Client\NetXClient;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DriverV12 extends AbstractHierarchicalFilesystemDriver
{

    const EXTENSION_KEY = 'netx_fal';
    const DRIVER_TYPE = 'FairwayNetXDriver';

    /** @var $client NetXClient */
    public static $client;
    /** @var Logger */
    protected $log;
    protected $instance;
    protected $configuration;
    protected $storageUid;

    public function __construct(array $configuration = [])
    {
        parent::__construct($configuration);

        $this->configuration = $configuration;
        $this->instance = rand();
        $this->log = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        $this->log->debug("$this->instance: __construct(" . json_encode($configuration) . ')');
        $this->capabilities = ResourceStorage::CAPABILITY_BROWSABLE | ResourceStorage::CAPABILITY_PUBLIC | ResourceStorage::CAPABILITY_HIERARCHICAL_IDENTIFIERS;
    }

    /**
     * Processes the configuration for this driver.
     */
    public function processConfiguration()
    {
        //$this->log->debug("$this->instance: processConfiguration()");
    }

    /**
     * Initializes this object. This is called by the storage after the driver
     * has been attached.
     */
    public function initialize()
    {
        //$this->log->debug("$this->instance: initialize()");
        self::$client = new NetXClient($this->configuration, $this->storageUid);
    }

    /**
     * Merges the capabilities merged by the user at the storage
     * configuration into the actual capabilities of the driver
     * and returns the result.
     *
     * @param int $capabilities
     * @return int
     */
    public function mergeConfigurationCapabilities($capabilities)
    {
        $this->capabilities &= $capabilities;
        //$this->log->debug("$this->instance: mergeConfigurationCapabilities($capabilities): $this->capabilities");
        return $this->capabilities;
    }

    /**
     * Returns the identifier of the root level folder of the storage.
     *
     * @return string
     */
    public function getRootLevelFolder()
    {
        //$this->log->debug("$this->instance: getRootLevelFolder(): " . self::ROOT_FOLDER_IDENTIFIER);
        return '/';
    }

    /**
     * Returns the identifier of the default folder new files should be put into.
     *
     * @return string
     */
    public function getDefaultFolder()
    {
        $ret = $this->getRootLevelFolder();
        $this->log->debug("$this->instance: getDefaultFolder(): $ret");
        return $ret;
    }

    /**
     * Returns the public URL to a file.
     * Either fully qualified URL or relative to PATH_site (rawurlencoded).
     *
     * @param string $identifier
     * @return string|null NULL if file is missing or deleted, the generated url otherwise
     */
    public function getPublicUrl($identifier)
    {
        $ret = self::$client->getUrl($identifier);
        $this->log->debug("$this->instance: getPublicURL($identifier): $ret");
        return $ret;
    }

    /**
     * Creates a folder, within a parent folder.
     * If no parent folder is given, a root level folder will be created
     *
     * @param string $newFolderName
     * @param string $parentFolderIdentifier
     * @param bool $recursive
     * @return string the Identifier of the new folder
     * @throws Exception
     */
    public function createFolder($newFolderName, $parentFolderIdentifier = '', $recursive = false)
    {
        //$this->log->debug("$this->instance: createFolder($newFolderName, $parentFolderIdentifier, $recursive");
        throw new Exception('Storage is read-only.');
    }

    /**
     * Renames a folder in this storage.
     *
     * @param string $folderIdentifier
     * @param string $newName
     * @return array A map of old to new file identifiers of all affected resources
     * @throws Exception
     */
    public function renameFolder($folderIdentifier, $newName)
    {
        //$this->log->debug("$this->instance: renameFolder($folderIdentifier, $newName)");
        throw new Exception('Storage is read-only.');
    }

    /**
     * Removes a folder in filesystem.
     *
     * @param string $folderIdentifier
     * @param bool $deleteRecursively
     * @return bool
     * @throws Exception
     */
    public function deleteFolder($folderIdentifier, $deleteRecursively = false)
    {
        //$this->log->debug("$this->instance: deleteFolder($folderIdentifier, $deleteRecursively)");
        throw new Exception('Storage is read-only.');
    }

    /**
     * Checks if a file exists.
     *
     * @param string $fileIdentifier
     * @return bool
     */
    public function fileExists($fileIdentifier)
    {
        $ret = ((substr($fileIdentifier, -1, 1) != '/') and ($this->getFileInfoByIdentifier($fileIdentifier) !== null));
        $this->log->debug("$this->instance: fileExists($fileIdentifier): " . ($ret ? 'true' : 'false'));
        return $ret;
    }

    /**
     * Checks if a folder exists.
     *
     * @param string $folderIdentifier
     * @return bool
     */
    public function folderExists($folderIdentifier)
    {
        $folderIdentifier = rtrim($folderIdentifier, '/\\') . '/';
        $ret = (($folderIdentifier === '/') or ($this->getFolderInfoByIdentifier($folderIdentifier) !== null));
        $this->log->debug("$this->instance: folderExists($folderIdentifier): " . ($ret ? 'true' : 'false'));
        return $ret;
    }

    /**
     * Checks if a folder contains files and (if supported) other folders.
     *
     * @param string $folderIdentifier
     * @return bool TRUE if there are no files and folders within $folder
     */
    public function isFolderEmpty($folderIdentifier)
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
     * @param string $localFilePath (within PATH_site)
     * @param string $targetFolderIdentifier
     * @param string $newFileName optional, if not given original name is used
     * @param bool $removeOriginal if set the original file will be removed
     *                                after successful operation
     * @return string the identifier of the new file
     * @throws Exception
     */
    public function addFile($localFilePath, $targetFolderIdentifier, $newFileName = '', $removeOriginal = true)
    {
        //$this->log->debug("$this->instance: addFile($localFilePath, $targetFolderIdentifier, $newFileName, $removeOriginal)");
        throw new Exception('Storage is read-only.');
    }

    /**
     * Creates a new (empty) file and returns the identifier.
     *
     * @param string $fileName
     * @param string $parentFolderIdentifier
     * @return string
     * @throws Exception
     */
    public function createFile($fileName, $parentFolderIdentifier)
    {
        //$this->log->debug("$this->instance: createFile($fileName, $parentFolderIdentifier)");
        throw new Exception('Storage is read-only.');
    }

    /**
     * Copies a file *within* the current storage.
     * Note that this is only about an inner storage copy action,
     * where a file is just copied to another folder in the same storage.
     *
     * @param string $fileIdentifier
     * @param string $targetFolderIdentifier
     * @param string $fileName
     * @return string the Identifier of the new file
     * @throws Exception
     */
    public function copyFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $fileName)
    {
        //$this->log->debug("$this->instance: copyFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $fileName)");
        throw new Exception('Storage is read-only.');
    }

    /**
     * Renames a file in this storage.
     *
     * @param string $fileIdentifier
     * @param string $newName The target path (including the file name!)
     * @return string The identifier of the file after renaming
     * @throws Exception
     */
    public function renameFile($fileIdentifier, $newName)
    {
        //$this->log->debug("$this->instance: renameFile($fileIdentifier, $newName)");
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
    public function replaceFile($fileIdentifier, $localFilePath)
    {
        //$this->log->debug("$this->instance: replaceFile($fileIdentifier, $localFilePath)");
        throw new Exception('Storage is read-only.');
    }

    /**
     * Removes a file from the filesystem. This does not check if the file is
     * still used or if it is a bad idea to delete it for some other reason
     * this has to be taken care of in the upper layers (e.g. the Storage)!
     *
     * @param string $fileIdentifier
     * @return bool TRUE if deleting the file succeeded
     * @throws Exception
     */
    public function deleteFile($fileIdentifier)
    {
        //$this->log->debug("$this->instance: deleteFile($fileIdentifier)");
        throw new Exception('Storage is read-only.');
    }

    /**
     * Creates a hash for a file.
     *
     * @param string $fileIdentifier
     * @param string $hashAlgorithm The hash algorithm to use
     * @return string
     */
    public function hash($fileIdentifier, $hashAlgorithm)
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
     * @param string $fileIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFileName
     * @return string
     * @throws Exception
     */
    public function moveFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $newFileName)
    {
        //$this->log->debug("$this->instance: moveFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $newFileName)");
        throw new Exception('Storage is read-only.');
    }

    /**
     * Folder equivalent to moveFileWithinStorage().
     *
     * @param string $sourceFolderIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFolderName
     * @return array All files which are affected, map of old => new file identifiers
     * @throws Exception
     */
    public function moveFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName)
    {
        //$this->log->debug("$this->instance: moveFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName)");
        throw new Exception('Storage is read-only.');
    }

    /**
     * Folder equivalent to copyFileWithinStorage().
     *
     * @param string $sourceFolderIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFolderName
     * @return bool
     * @throws Exception
     */
    public function copyFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName)
    {
        //$this->log->debug("$this->instance: copyFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName)");
        throw new Exception('Storage is read-only.');
    }

    /**
     * Returns the contents of a file. Beware that this requires to load the
     * complete file into memory and also may require fetching the file from an
     * external location. So this might be an expensive operation (both in terms
     * of processing resources and money) for large files.
     *
     * @param string $fileIdentifier
     * @return string The file contents
     */
    public function getFileContents($fileIdentifier)
    {
        $streamContext = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header' => [
                    self::$client->createAuthenticationHeader(),
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
        return file_get_contents(self::$client->getUrl($fileIdentifier), false, $streamContext);
    }

    /**
     * Sets the contents of a file to the specified value.
     *
     * @param string $fileIdentifier
     * @param string $contents
     * @return int The number of bytes written to the file
     * @throws Exception
     */
    public function setFileContents($fileIdentifier, $contents)
    {
        //$this->log->debug("$this->instance: setFileContents($fileIdentifier, $contents)");
        throw new Exception('Storage is read-only.');
    }

    /**
     * Checks if a file inside a folder exists
     *
     * @param string $fileName
     * @param string $folderIdentifier
     * @return bool
     * @throws Exception
     */
    public function fileExistsInFolder($fileName, $folderIdentifier)
    {
        return array_key_exists($fileName, self::$client->getFolderInfo($folderIdentifier, 'filename'));
    }

    /**
     * Checks if a folder inside a folder exists.
     *
     * @param string $folderName
     * @param string $folderIdentifier
     * @return bool
     * @throws Exception
     */
    public function folderExistsInFolder($folderName, $folderIdentifier)
    {
        //$this->log->debug("$this->instance: folderExistsInFolder($folderName, $folderIdentifier)");
        return array_key_exists($folderName, self::$client->getFolderInfo($folderIdentifier, 'foldername'));
    }

    /**
     * Returns a path to a local copy of a file for processing it. When changing the
     * file, you have to take care of replacing the current version yourself!
     *
     * @param string $fileIdentifier
     * @param bool $writable Set this to FALSE if you only need the file for read
     *                       operations. This might speed up things, e.g. by using
     *                       a cached local version. Never modify the file if you
     *                       have set this flag!
     * @return string The path to the file on the local disk
     */
    public function getFileForLocalProcessing($fileIdentifier, $writable = true)
    {

        $tmp = GeneralUtility::tempnam('fal-tempfile-', '.' . self::$client->getFileInfo($fileIdentifier)['info']['extension']);
        $streamContext = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header' => [
                    self::$client->createAuthenticationHeader(),
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
        $stream = @fopen(self::$client->getUrl($fileIdentifier), 'r', false, $streamContext);
        $status = file_put_contents($tmp, $stream);
        @fclose($stream);
        return $tmp;
    }

    /**
     * Returns the permissions of a file/folder as an array
     * (keys r, w) of boolean flags
     *
     * @param string $identifier
     * @return array
     */
    public function getPermissions($identifier)
    {
        //$this->log->debug("$this->instance: getPermissions($identifier)");
        return ['r' => true, 'w' => false];
    }

    /**
     * Directly output the contents of the file to the output
     * buffer. Should not take care of header files or flushing
     * buffer before. Will be taken care of by the Storage.
     *
     * @param string $identifier
     */
    public function dumpFileContents($identifier)
    {
        //$this->log->debug("$this->instance: dumpFileContents($identifier)");
        $handle = fopen('php://output', 'w');
        fwrite($handle, file_get_contents(self::$client->getUrl($identifier))); // ex thumbnail
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
     *
     * @param string $folderIdentifier
     * @param string $identifier identifier to be checked against $folderIdentifier
     * @return bool TRUE if $content is within or matches $folderIdentifier
     */
    public function isWithin($folderIdentifier, $identifier)
    {
        $folderIdentifier = rtrim($folderIdentifier, '/\\') . '/';
        $id = rtrim($identifier, '/\\') . '/';
        $ret = ($identifier and (strpos($id, $folderIdentifier) === 0));
        $this->log->debug("$this->instance: isWithin($folderIdentifier, $identifier): " . $ret ? 'true' : 'false');
        return $ret;
    }

    /**
     * Returns information about a file.
     *
     * @param string $fileIdentifier
     * @param array $propertiesToExtract Array of properties which are be extracted
     *                                   If empty all will be extracted
     * @return array
     */
    public function getFileInfoByIdentifier($fileIdentifier, array $propertiesToExtract = [])
    {
        $ret = self::$client->getFileInfo($fileIdentifier)['info'];
        $this->log->debug("$this->instance: getFileInfoByIdentifier($fileIdentifier, " . json_encode($propertiesToExtract) . '): ' . json_encode($ret));
        return $ret;
    }

    /**
     * Returns information about a file.
     *
     * @param string $folderIdentifier
     * @return array
     */
    public function getFolderInfoByIdentifier($folderIdentifier)
    {
        $folderIdentifier = rtrim($folderIdentifier, '/\\') . '/';
        $ret = self::$client->getFolderInfo($folderIdentifier)['info'];
        //$this->log->debug("$this->instance: getFolderInfoByIdentifier($folderIdentifier): " . json_encode($ret));
        return $ret;
    }

    /**
     * Returns the identifier of a file inside the folder
     *
     * @param string $fileName
     * @param string $folderIdentifier
     * @return string file identifier
     * @throws Exception
     */
    public function getFileInFolder($fileName, $folderIdentifier)
    {
        //$this->log->debug("$this->instance: getFileInFolder($fileName, $folderIdentifier)");
        return self::$client->getFolderInfo($folderIdentifier, 'filename')[$fileName];
    }

    /**
     * Returns a list of files inside the specified path
     *
     * @param string $folderIdentifier
     * @param int $start
     * @param int $numberOfItems
     * @param bool $recursive
     * @param array $filenameFilterCallbacks callbacks for filtering the items
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return array of FileIdentifiers
     */
    public function getFilesInFolder($folderIdentifier, $start = 0, $numberOfItems = 0, $recursive = false, array $filenameFilterCallbacks = [], $sort = '', $sortRev = false)
    {
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
            $ret = array_slice($ret, $start >= 0 ? $start : 0, $numberOfItems <= 0 ? null : $numberOfItems);
        }
        //$this->log->debug("$this->instance: getFilesInFolder($folderIdentifier, $start, $numberOfItems, $recursive, " . json_encode($filenameFilterCallbacks) . ", $sort, $sortRev): " . json_encode($ret));
        return $ret;
    }

    /**
     * Returns the identifier of a folder inside the folder
     *
     * @param string $folderName The name of the target folder
     * @param string $folderIdentifier
     * @return string folder identifier
     * @throws Exception
     */
    public function getFolderInFolder($folderName, $folderIdentifier)
    {
        //$this->log->debug("$this->instance: getFolderInFolder($folderName, $folderIdentifier)");
        return self::$client->getFolderInfo($folderIdentifier, 'foldername')[$folderName];
    }

    /**
     * Returns a list of folders inside the specified path
     *
     * @param string $folderIdentifier
     * @param int $start
     * @param int $numberOfItems
     * @param bool $recursive
     * @param array $folderNameFilterCallbacks callbacks for filtering the items
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return array of Folder Identifier
     */
    public function getFoldersInFolder($folderIdentifier, $start = 0, $numberOfItems = 0, $recursive = false, array $folderNameFilterCallbacks = [], $sort = '', $sortRev = false)
    {
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
            usort($data, function ($a, $b) use ($sortRev, $sort) {
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
            $ret = array_slice($ret, $start >= 0 ? $start : 0, $numberOfItems <= 0 ? null : $numberOfItems);
        }
        //$this->log->debug("$this->instance: getFoldersInFolder($folderIdentifier, $start, $numberOfItems, $recursive, " . json_encode($folderNameFilterCallbacks) . "$sort, $sortRev): " . json_encode($ret));
        return $ret;
    }

    /**
     * Returns the number of files inside the specified path
     *
     * @param string $folderIdentifier
     * @param bool $recursive
     * @param array $filenameFilterCallbacks callbacks for filtering the items
     * @return int Number of files in folder
     */
    public function countFilesInFolder($folderIdentifier, $recursive = false, array $filenameFilterCallbacks = [])
    {
        $ret = count($this->getFilesInFolder($folderIdentifier, 0, 0, $recursive, $filenameFilterCallbacks));
        $this->log->debug("$this->instance: countFilesInFolder($folderIdentifier, $recursive, " . json_encode($filenameFilterCallbacks) . "): $ret");
        return $ret;
    }

    /**
     * Returns the number of folders inside the specified path
     *
     * @param string $folderIdentifier
     * @param bool $recursive
     * @param array $folderNameFilterCallbacks callbacks for filtering the items
     * @return int Number of folders in folder
     */
    public function countFoldersInFolder($folderIdentifier, $recursive = false, array $folderNameFilterCallbacks = [])
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
    public function setStorageUid($storageUid)
    {
        //$this->log->debug("$this->instance: setStorageUid($storageUid)");
        $this->storageUid = $storageUid;
    }

    /**
     * Returns the capabilities of this driver.
     *
     * @return int
     * @see Storage::CAPABILITY_* constants
     */
    public function getCapabilities()
    {
        //$this->log->debug("$this->instance: getCapabilities(): $this->capabilities");
        return $this->capabilities;
    }

    /**
     * Returns TRUE if this driver has the given capability.
     *
     * @param int $capability A capability, as defined in a CAPABILITY_* constant
     * @return bool
     */
    public function hasCapability($capability)
    {
        $ret = ($this->capabilities & $capability) === $capability;
        $this->log->debug("$this->instance: hasCapability($capability): $ret");
        return $ret;
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
    public function isCaseSensitiveFileSystem()
    {
        //$this->log->debug("$this->instance: isCaseSensitiveFileSystem(): true");
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
    public function sanitizeFileName($fileName, $charset = '')
    {
        //$this->log->debug("$this->instance: sanitizeFileName($fileName, $charset): $fileName");
        return $fileName;
    }

    /**
     * Hashes a file identifier, taking the case sensitivity of the file system
     * into account. This helps mitigating problems with case-insensitive
     * databases.
     *
     * @param string $identifier
     * @return string
     */
    public function hashIdentifier($identifier)
    {
        $ret = sha1($identifier);
        $this->log->debug("$this->instance: hashIdentifier($identifier): $ret");
        return $ret;
    }

    /**
     * Returns the identifier of the folder the file resides in
     *
     * @param string $fileIdentifier
     * @return string
     */
    public function getParentFolderIdentifierOfIdentifier($fileIdentifier)
    {
        $ret = rtrim(dirname($fileIdentifier), '/\\') . '/';
        $this->log->debug("$this->instance: getParentFolderIdentifierOfIdentifier($fileIdentifier): $ret");
        return $ret;
    }

}
