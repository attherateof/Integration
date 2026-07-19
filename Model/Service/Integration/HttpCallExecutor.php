<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Service\Integration;

use MageStack\Integration\Model\Api\Caller;
use MageStack\Integration\Model\Service\ResolvedConfigProvider;

/**
 * Builds the request URL and dispatches the HTTP call for an endpoint.
 */
class HttpCallExecutor
{
    public function __construct(
        private readonly ResolvedConfigProvider $resolvedConfigProvider,
        private readonly Caller $caller
    ) {}

    public function execute(
        string $apiCode,
        string $endpointCode,
        string $websiteCode,
        array $requestPayload,
        array $requestUrlParams,
        array $requestHeaders,
        array $payload
    ): array {
        $path = $this->resolvedConfigProvider->getEndpointPath($apiCode, $endpointCode, $websiteCode);
        $method = $this->resolvedConfigProvider->getEndpointMethod($apiCode, $endpointCode, $websiteCode);
        $timeout = $this->resolvedConfigProvider->getEndpointTimeout($apiCode, $endpointCode, $websiteCode);

        $url = $this->buildUrl($path, $requestUrlParams);
        $headers = array_merge($payload['request_header'] ?? [], $requestHeaders);

        return $this->caller->call(
            $method,
            $url,
            $headers,
            $requestPayload,
            $timeout
        );
    }

    private function buildUrl(string $path, array $urlParams = []): string
    {
        $url = rtrim($path, '/');

        $queryParams = $urlParams['query'] ?? [];
        unset($urlParams['query']);

        foreach ($urlParams as $key => $value) {
            $url = str_replace(
                '{' . $key . '}',
                rawurlencode((string) $value),
                $url
            );
        }

        if (preg_match('/\{[^}]+\}/', $url)) {
            throw new \InvalidArgumentException(
                sprintf('URL contains unresolved placeholders: %s', $url)
            );
        }

        if (!empty($queryParams)) {
            $url .= (str_contains($url, '?') ? '&' : '?')
                . http_build_query($queryParams);
        }

        return $url;
    }
}
