<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Auth\Pool;

use MageStack\Integration\Api\Auth\AuthenticationProviderInterface;

abstract class AbstractProvider implements AuthenticationProviderInterface
{
    public function buildHeaders(
        array $apiConfig,
        int $websiteId
    ): array {
        return [];
    }

    public function buildQueryParams(
        array $apiConfig,
        int $websiteId
    ): array {
        return [];
    }

    public function getConfigurationFields(): array
    {
        return [];
    }
}
