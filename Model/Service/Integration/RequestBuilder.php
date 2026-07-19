<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Service\Integration;

use InvalidArgumentException;

class RequestBuilder
{
    private ?string $apiCode = null;

    private ?string $endpointCode = null;

    private ?string $websiteCode = null;

    private array $data = [];

    private int $attempt = 0;

    public function setApiCode(string $apiCode): self
    {
        if ($this->apiCode === '') {
            throw new InvalidArgumentException('API code is required.');
        }

        $this->apiCode = $apiCode;

        return $this;
    }

    public function setEndpointCode(string $endpointCode): self
    {
        if ($this->endpointCode === '') {
            throw new InvalidArgumentException('Endpoint code is required.');
        }

        $this->endpointCode = $endpointCode;

        return $this;
    }

    public function setWebsiteCode(string $websiteCode): self
    {
        if ($this->websiteCode === null || $this->websiteCode === '') {
            throw new InvalidArgumentException('Website code is required.');
        }

        $this->websiteCode = $websiteCode;

        return $this;
    }

    public function setData(array $input): self
    {
        $this->data = $input;

        return $this;
    }

    public function setAttempt(int $attempt = 0): self
    {
        if ($this->attempt < 0) {
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

    public function getInput(): array
    {
        return $this->data;
    }

    public function getAttempt(): int
    {
        return $this->attempt;
    }
}
