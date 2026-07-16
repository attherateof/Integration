<?php

declare(strict_types=1);

namespace MageStack\Integration\Model;

use Magento\Framework\Model\AbstractModel;
use MageStack\Integration\Api\Data\ApiAttemptInterface;
use Override;

class ApiAttempt extends AbstractModel implements ApiAttemptInterface
{
    protected function _construct(): void
    {
        $this->_init(\MageStack\Integration\Model\ResourceModel\ApiAttempt::class);
    }

    public function getId(): ?int
    {
        $id = $this->getData(self::ATTEMPT_ID);
        return $id === null ? null : (int) $id;
    }

    public function getApiCode(): ?string
    {
        return $this->getData(self::API_CODE);
    }

    public function setApiCode(string $code): self
    {
        $this->setData(self::API_CODE, $code);
        return $this;
    }

    public function getEndpointCode(): ?string
    {
        return $this->getData(self::ENDPOINT_CODE);
    }

    public function setEndpointCode(string $code): self
    {
        $this->setData(self::ENDPOINT_CODE, $code);
        return $this;
    }

    public function getWebsiteCode(): ?string
    {
        return $this->getData(self::WEBSITE_CODE);
    }

    public function setWebsiteCode(string $code): self
    {
        $this->setData(self::WEBSITE_CODE, $code);
        return $this;
    }

    public function getPayload(): ?string
    {
        return $this->getData(self::PAYLOAD);
    }

    public function setPayload(?string $payload): self
    {
        $this->setData(self::PAYLOAD, $payload);
        return $this;
    }

    public function getUrlParams(): ?string
    {
        return $this->getData(self::URL_PARAMS);
    }

    public function setUrlParams(?string $params): self
    {
        $this->setData(self::URL_PARAMS, $params);
        return $this;
    }

    public function getAttempt(): int
    {
        return (int) $this->getData(self::ATTEMPT) ?: 0;
    }

    public function setAttempt(int $attempt): self
    {
        $this->setData(self::ATTEMPT, $attempt);
        return $this;
    }

    public function getMaxAttempt(): int
    {
        return (int) $this->getData(self::MAX_ATTEMPT) ?: 0;
    }

    public function setMaxAttempt(int $maxAttempt): self
    {
        $this->setData(self::MAX_ATTEMPT, $maxAttempt);
        return $this;
    }

    public function getNextAttemptAt(): ?string
    {
        return $this->getData(self::NEXT_ATTEMPT_AT);
    }

    public function setNextAttemptAt(?string $date): self
    {
        $this->setData(self::NEXT_ATTEMPT_AT, $date);
        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    public function setCreatedAt(string $date): self
    {
        $this->setData(self::CREATED_AT, $date);
        return $this;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    public function setUpdatedAt(string $date): self
    {
        $this->setData(self::UPDATED_AT, $date);
        return $this;
    }
}
