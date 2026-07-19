<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Service\Integration;

use InvalidArgumentException;

class IntegrationBuilder
{
    private ?string $apiCode = null;

    private ?string $endpointCode = null;

    private ?string $websiteCode = null;

    private array $data = [];
    private array $urlParams = [];
    private array $headers = [];

    private int $attempt = 0;

    public function setApiCode(string $apiCode): self
    {
        if ($apiCode === '') {
            throw new InvalidArgumentException('API code is required.');
        }

        $this->apiCode = $apiCode;

        return $this;
    }

    public function setEndpointCode(string $endpointCode): self
    {
        if ($endpointCode === '') {
            throw new InvalidArgumentException('Endpoint code is required.');
        }

        $this->endpointCode = $endpointCode;

        return $this;
    }

    public function setWebsiteCode(string $websiteCode): self
    {
        if ($websiteCode === '') {
            throw new InvalidArgumentException('Website code is required.');
        }

        $this->websiteCode = $websiteCode;

        return $this;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function setUrlParams(array $urlParams): self
    {
        $this->urlParams = $urlParams;

        return $this;
    }

    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    public function setAttempt(int $attempt = 0): self
    {
        if ($attempt < 0) {
            throw new InvalidArgumentException('Attempt cannot be negative.');
        }

        $this->attempt = $attempt;

        return $this;
    }

    public function getApiCode(): ?string
    {
        return $this->apiCode;
    }

    public function getEndpointCode(): ?string
    {
        return $this->endpointCode;
    }

    public function getWebsiteCode(): ?string
    {

        return $this->websiteCode;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getHeaders(): array
    {
        return $this->headers ?? [];
    }

    public function getUrlParams(): array
    {
        return $this->urlParams ?? [];
    }

    public function getAttempt(): int
    {
        return $this->attempt;
    }
}
