<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Api;

use Psr\Log\LoggerInterface;

class CacheManager
{
    public function __construct(
        private readonly ResponseCacheRepository $cacheRepository,
        private readonly LoggerInterface $logger
    ) {}

    public function get(
        string $keyPrefix,
        string $apiCode,
        string $endpointCode,
        string $websiteCode,
        array $payload,
        array $urlParams
    ): array|false {
        $cacheKey = $this->buildCacheKey($keyPrefix, $apiCode, $endpointCode, $websiteCode, $payload, $urlParams);

        return $this->cacheRepository->get($cacheKey);
    }

    public function save(
        string $keyPrefix,
        string $apiCode,
        string $endpointCode,
        string $websiteCode,
        array $response,
        ?int $ttl,
        array $payload,
        array $urlParams
    ): void {
        $cacheKey = $this->buildCacheKey($keyPrefix, $apiCode, $endpointCode, $websiteCode, $payload, $urlParams);

        try {
            $this->cacheRepository->save($cacheKey, $response, $ttl);
        } catch (\Throwable $exception) {
            $this->logger->warning(
                sprintf('Failed to save API cache for api=%s endpoint=%s website=%s: %s', $apiCode, $endpointCode, $websiteCode, $exception->getMessage())
            );
        }
    }

    private function buildCacheKey(
        string $keyPrefix,
        string $apiCode,
        string $endpointCode,
        string $websiteCode,
        array $payload,
        array $urlParams
    ): string {
        $key = $apiCode
            . '_' . $endpointCode
            . '_' . $websiteCode
            . '_' . $this->normalizeValue($payload)
            . '_' . $this->normalizeValue($urlParams);

        if ($keyPrefix !== '') {
            $key = $keyPrefix . '_' . $key;
        }

        return md5($key);
    }

    private function normalizeValue(array $data): string
    {
        ksort($data);

        return json_encode($data, JSON_THROW_ON_ERROR);
    }
}
