<?php

declare(strict_types=1);

/*
 * This file is part of the "netx_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\NetXFal\Utility;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Cache implements SingletonInterface
{
    private ?FrontendInterface $cache = null;
    /** @var array<string, mixed> */
    private array $cacheData = [];

    public function __construct()
    {
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);

        $driverClass = DriverUtility::getDriver();
        if ($cacheManager->hasCache($driverClass::EXTENSION_KEY)) {
            $this->cache = $cacheManager->getCache($driverClass::EXTENSION_KEY);
        }
    }

    public function has(string $entryIdentifier): bool
    {
        if ($this->cache) {
            return $this->cache->has($entryIdentifier);
        }

        return array_key_exists($entryIdentifier, $this->cacheData);
    }

    /**
     * @param list<string> $tags
     */
    public function set(string $entryIdentifier, mixed $data, array $tags = [], ?int $lifetime = null): void
    {
        if ($this->cache) {
            $this->cache->set($entryIdentifier, $data, $tags, $lifetime);
            return;
        }

        $this->cacheData[$entryIdentifier] = $data;
    }

    public function get(string $entryIdentifier): mixed
    {
        if ($this->cache) {
            return $this->cache->get($entryIdentifier);
        }

        return $this->cacheData[$entryIdentifier];
    }

    /**
     * clear the netx_fal cache
     */
    public function clearCache(): ResponseInterface
    {
        if ($this->cache) {
            $this->cache->flush();
        }
        $this->cacheData = [];
        $result = ['success' => true, 'title' => 'Success', 'message' => 'NetX cache successfully cleared.'];
        return new JsonResponse($result);
    }
}
