<?php

declare(strict_types=1);

namespace MageStack\Integration\Model;

class IntegrationContext
{
    private ?string $apiCode = null;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $apiConfig = null;

    public function setApiCode(
        string $apiCode
    ): void {
        $this->apiCode = $apiCode;
    }

    public function getApiCode(): ?string
    {
        return $this->apiCode;
    }

    /**
     * @param array<string, mixed> $apiConfig
     */
    public function setApiConfig(
        array $apiConfig
    ): void {
        $this->apiConfig = $apiConfig;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getApiConfig(): ?array
    {
        return $this->apiConfig;
    }
}
