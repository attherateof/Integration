<?php

declare(strict_types=1);

namespace MageStack\Integration\Model;

use InvalidArgumentException;
use MageStack\Integration\Model\Cache\Repository as IntegrationCacheRepository;

class ConfigResolver
{
    public function __construct(
        private readonly IntegrationConfig $config,
        private readonly IntegrationCacheRepository $cacheRepository
    ) {}

    public function resolve(
        string $apiCode,
        string $websiteCode
    ): array {
        $cached = $this->cacheRepository->load($apiCode, $websiteCode);

        if ($cached !== false) {
            return $cached;
        }

        $api = $this->config->resolve()['apis'][$apiCode] ?? null;

        if (!$api) {
            throw new InvalidArgumentException(
                sprintf('API "%s" not found.', $apiCode)
            );
        }

        $website = $api['websites'][$websiteCode] ?? [];

        $resolved = [
            'enabled' => $api['enabled'] ?? true,
            'authentication' => $this->resolveAuthentication(
                $api,
                $website
            ),
            'endpoints' => $this->resolveEndpoints(
                $api,
                $website
            )
        ];

        $this->cacheRepository->save($resolved, $apiCode, $websiteCode);

        return $resolved;
    }

    private function resolveAuthentication(
        array $api,
        array $website
    ): array {
        $authentication = $this->merge(
            $api['authentication'] ?? [],
            $website['authentication'] ?? []
        );

        if (!empty($authentication['token_endpoint'])) {
            $authentication['token_endpoint'] =
                $this->buildUrl(
                    $website['base_url']
                        ?? $api['base_url'],
                    $authentication['token_endpoint']
                );
        }

        return $authentication;
    }

    private function resolveEndpoints(
        array $api,
        array $website
    ): array {
        $resolved = [];

        foreach ($api['endpoints'] ?? [] as $endpointCode => $endpoint) {
            $resolved[$endpointCode] = $this->resolveEndpoint(
                $endpointCode,
                $endpoint,
                $website['endpoints'][$endpointCode] ?? [],
                $api,
                $website
            );
        }

        return $resolved;
    }

    /**
     * format config
     *
     * @param string $endpointCode
     * @param array $globalEndpoint
     * @param array $websiteEndpoint
     * @param array $api
     * @param array $website
     * @return array
     */
    private function resolveEndpoint(
        string $endpointCode,
        array $globalEndpoint,
        array $websiteEndpoint,
        array $api,
        array $website
    ): array {
        $endpoint = $this->merge(
            $globalEndpoint,
            $websiteEndpoint
        );

        $baseUrl = $website['base_url']
            ?? $api['base_url'];

        $method = $endpoint['method'] ?? null;

        return [
            'method' => $method,
            'path' => $this->buildUrl(
                $baseUrl,
                $endpoint['path'] ?? ''
            ),
            'guest' => $endpoint['guest'] ?? false,
            'enabled' => $endpoint['enabled']
                ?? true,
            'timeout' => $endpoint['timeout']
                ?? $website['timeout']
                ?? $api['timeout']
                ?? null,

            'cache' => $this->resolveCache(
                $endpointCode,
                $method,
                $globalEndpoint,
                $websiteEndpoint,
                $api,
                $website
            ),

            'retry' => $this->resolveRetry(
                $globalEndpoint,
                $websiteEndpoint,
                $api,
                $website
            ),

            'request_builder' => $endpoint['request_builder']
                ?? null,

            'response_handler' => $endpoint['response_handler']
                ?? null,

            'response_validator' => $endpoint['response_validator']
                ?? null,
        ];
    }

    private function resolveRetry(
        array $globalEndpoint,
        array $websiteEndpoint,
        array $api,
        array $website
    ): array {
        $retry = [];

        $retry = $this->merge(
            $retry,
            $api['retry'] ?? []
        );

        $retry = $this->merge(
            $retry,
            $globalEndpoint['retry'] ?? []
        );

        $retry = $this->merge(
            $retry,
            $website['retry'] ?? []
        );

        $retry = $this->merge(
            $retry,
            $websiteEndpoint['retry'] ?? []
        );

        return [
            'enabled' => $retry['enabled'] ?? false,
            'max_attempts' => $retry['max_attempts'] ?? null,
            'backoff_multiplier' => $retry['backoff_multiplier'] ?? null,
            'http_codes' => $retry['http_codes'] ?? [],
        ];
    }

    private function resolveCache(
        string $endpointCode,
        ?string $method,
        array $globalEndpoint,
        array $websiteEndpoint,
        array $api,
        array $website
    ): array {
        if ($method !== 'GET') {
            return [
                'enabled' => false,
                'ttl' => null,
                'key_prefix' => null,
            ];
        }

        $cache = [];

        $cache = $this->merge(
            $cache,
            $api['cache'] ?? []
        );

        $cache = $this->merge(
            $cache,
            $website['cache'] ?? []
        );

        $cache = $this->merge(
            $cache,
            $globalEndpoint['cache'] ?? []
        );

        $cache = $this->merge(
            $cache,
            $websiteEndpoint['cache'] ?? []
        );

        return [
            'enabled' => $cache['enabled'] ?? false,
            'ttl' => $cache['ttl'] ?? null,
            'key_prefix' => ($cache['enabled'] ?? false)
                ? ($api['cache']['key_prefix'] ?? 'cache')
                . '_' . $endpointCode
                : null,
        ];
    }

    private function merge(
        array $base,
        array $override
    ): array {
        foreach ($override as $key => $value) {
            if ($value === null) {
                continue;
            }

            if ($value === []) {
                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }

    private function buildUrl(
        string $baseUrl,
        string $path
    ): string {
        return rtrim($baseUrl, '/')
            . '/'
            . ltrim($path, '/');
    }
}
