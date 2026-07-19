<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Service\Integration;

use Magento\Framework\Exception\LocalizedException;
use MageStack\Integration\Model\Service\ResolvedConfigProvider;

/**
 * Validates a response against the endpoint's configured validator (if any)
 * and transforms it via the endpoint's configured handler (if any).
 */
class ResponseProcessor
{
    public function __construct(
        private readonly ResolvedConfigProvider $resolvedConfigProvider
    ) {}

    public function process(
        array $response,
        string $apiCode,
        string $endpointCode,
        string $websiteCode
    ): array {
        $this->validate($response, $apiCode, $endpointCode, $websiteCode);

        return $this->handle($response, $apiCode, $endpointCode, $websiteCode);
    }

    private function validate(
        array $response,
        string $apiCode,
        string $endpointCode,
        string $websiteCode
    ): void {
        $validator = $this->resolvedConfigProvider->getEndpointResponseValidator($apiCode, $endpointCode, $websiteCode);

        if ($validator !== null && !$validator->validate($response)) {
            throw new LocalizedException(
                __('Response validation failed for endpoint "%1".', $endpointCode)
            );
        }
    }

    private function handle(
        array $response,
        string $apiCode,
        string $endpointCode,
        string $websiteCode
    ): array {
        $handler = $this->resolvedConfigProvider->getEndpointResponseHandler($apiCode, $endpointCode, $websiteCode);

        return $handler !== null ? $handler->handle($response) : $response;
    }
}
