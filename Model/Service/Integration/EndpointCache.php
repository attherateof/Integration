<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Service\Integration;

use MageStack\Integration\Model\Api\CacheManager;
use MageStack\Integration\Model\Service\ResolvedConfigProvider;

/**
 * Wraps endpoint-scoped response caching: whether it's enabled, fetching a
 * cached response, and storing a fresh successful response.
 */
class EndpointCache
{
    public function __construct(
        private readonly ResolvedConfigProvider $resolvedConfigProvider,
        private readonly CacheManager $cacheManager
    ) {}

    public function isEnabled(string $apiCode, string $endpointCode, string $websiteCode): bool
    {
        return $this->resolvedConfigProvider->isEndpointCacheEnabled($apiCode, $endpointCode, $websiteCode);
    }

    public function fetch(
        string $apiCode,
        string $endpointCode,
        string $websiteCode,
        array $requestPayload,
        array $requestUrlParams
    ): array|false {
        $cacheKey = (string) $this->resolvedConfigProvider->getEndpointCacheKey($apiCode, $endpointCode, $websiteCode);

        return $this->cacheManager->get(
            $cacheKey,
            $apiCode,
            $endpointCode,
            $websiteCode,
            $requestPayload,
            $requestUrlParams
        );
    }

    /**
     * Stores the response only if it represents a successful (2xx) call;
     * callers don't need to know or check that themselves.
     */
    public function store(
        string $apiCode,
        string $endpointCode,
        string $websiteCode,
        array $response,
        array $requestPayload,
        array $requestUrlParams
    ): void {
        $statusCode = (int) ($response['status'] ?? 0);

        if ($statusCode < 200 || $statusCode >= 300) {
            return;
        }

        $cacheKey = (string) $this->resolvedConfigProvider->getEndpointCacheKey($apiCode, $endpointCode, $websiteCode);
        $ttl = $this->resolvedConfigProvider->getEndpointCacheTtl($apiCode, $endpointCode, $websiteCode);

        $this->cacheManager->save(
            $cacheKey,
            $apiCode,
            $endpointCode,
            $websiteCode,
            $response,
            $ttl,
            $requestPayload,
            $requestUrlParams
        );
    }
}
