<?php

declare(strict_types=1);

namespace MageStack\Integration\Api\Config;

interface RequestBuilderInterface
{
    /**
     * Build the request payload and URL parameters from configuration.
     *
     * @param int $websiteId
     * @param array<int|string, mixed> $apiConfig
     * @param array<int|string, mixed> $payload
     * @param array<int|string, mixed> $urlParams
     * @return array{payload: array<int|string, mixed>, urlParams: array<int|string, mixed>}
     */
    public function build(int $websiteId, array $apiConfig, array $payload, array $urlParams): array;
}
