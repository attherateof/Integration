<?php

declare(strict_types=1);

namespace MageStack\Integration\Api\Auth;

use MageStack\Integration\Model\Service\Integration\IntegrationBuilder;

interface AuthenticationProviderInterface
{
    /**
     * Unique auth type.
     */
    public function getType(): string;

    /**
     * Return headers.
     * TODO:: change method name to get auth credentials or more suitable one
     */
    public function buildHeaders(
        IntegrationBuilder $integrationRequest
    ): array;

    /**
     * Return query params if auth is passed via URL.
     */
    public function buildQueryParams(
        IntegrationBuilder $integrationRequest
    ): array;

    /**
     * Return admin credential field definitions.
     */
    public function getConfigurationFields(): array;
}
