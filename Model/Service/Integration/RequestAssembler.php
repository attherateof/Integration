<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Service\Integration;

use MageStack\Integration\Model\Service\ResolvedConfigProvider;
use MageStack\Integration\Model\Service\Integration\IntegrationBuilder;

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
        IntegrationBuilder $request
    ): array {
        $requestBuilder = $this->resolvedConfigProvider->getEndpointRequestBuilder(
            $request->getApiCode(),
            $request->getEndpointCode(),
            $request->getWebsiteCode()
        );

        return $requestBuilder !== null ? $requestBuilder->build($request) : [
            $request->getHeaders(),
            $request->getData(),
            $request->getUrlParams()
        ];
    }
}
