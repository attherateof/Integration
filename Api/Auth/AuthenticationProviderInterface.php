<?php

declare(strict_types=1);

namespace MageStack\Integration\Api\Auth;

interface AuthenticationProviderInterface
{
    /**
     * Unique auth type.
     */
    public function getType(): string;

    /**
     * Return headers.
     */
    public function buildHeaders(
        array $apiConfig,
        int $websiteId
    ): array;

    /**
     * Return query params if auth is passed via URL.
     */
    public function buildQueryParams(
        array $apiConfig,
        int $websiteId
    ): array;

    /**
     * Return admin credential field definitions.
     */
    public function getConfigurationFields(): array;
}