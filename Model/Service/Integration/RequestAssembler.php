<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Service\Integration;

use MageStack\Integration\Model\Service\ResolvedConfigProvider;

/**
 * Resolves the endpoint's configured request builder and assembles the
 * outgoing payload, url params, and headers for a single API call.
 */
class RequestAssembler
{
    public function __construct(
        private readonly ResolvedConfigProvider $resolvedConfigProvider
    ) {}

    public function assemble(
        int $websiteId,
        string $apiCode,
        string $endpointCode,
        string $websiteCode,
        array $payload,
        array $urlParams
    ): array {
        $requestBuilder = $this->resolvedConfigProvider->getEndpointRequestBuilder($apiCode, $endpointCode, $websiteCode);

        // TODO:: next line will change once we have an endpoint-scoped request builder
        // $resolvedConfig = $this->resolvedConfigProvider->getResolvedConfig($apiCode, $websiteCode);

        // $built = $requestBuilder->build($websiteId, $resolvedConfig, $payload, $urlParams);
        $built = [];
        return [
            $built['payload'] ?? [],
            $built['urlParams'] ?? [],
            $built['headers'] ?? [],
        ];
    }
}
