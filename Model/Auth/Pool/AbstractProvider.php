<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Auth\Pool;

use MageStack\Integration\Api\Auth\AuthenticationProviderInterface;
use MageStack\Integration\Model\Service\Integration\IntegrationBuilder;

abstract class AbstractProvider implements AuthenticationProviderInterface
{
    public function buildHeaders(
        IntegrationBuilder $integrationRequest
    ): array {
        return [];
    }

    public function buildQueryParams(
        IntegrationBuilder $integrationRequest
    ): array {
        return [];
    }

    public function getConfigurationFields(): array
    {
        return [];
    }
}
