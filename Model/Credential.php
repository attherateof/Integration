<?php

declare(strict_types=1);

namespace MageStack\Integration\Model;

use MageStack\Integration\Api\Data\CredentialInterface;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use MageStack\Integration\Model\ResourceModel\Credential as ResourceCredential;

class Credential extends AbstractModel implements CredentialInterface, IdentityInterface
{
    public const CACHE_TAG = 'magestack_integration_credential';

    protected $_cacheTag = self::CACHE_TAG;

    protected $_eventPrefix = 'magestack_integration_credential';

    protected function _construct(): void
    {
        $this->_init(
            ResourceCredential::class
        );
    }

    public function getIdentities(): array
    {
        return [
            self::CACHE_TAG . '_' . $this->getId()
        ];
    }

    public function getApiCode(): string
    {
        return (string)$this->getData(self::API_CODE);
    }

    public function setApiCode(string $apiCode): self
    {
        $this->setData(self::API_CODE, $apiCode);

        return $this;
    }

    public function getWebsiteId(): int
    {
        return (int)$this->getData(self::WEBSITE_ID);
    }

    public function setWebsiteId(int $websiteId): self
    {
        $this->setData(self::WEBSITE_ID, $websiteId);

        return $this;
    }

    public function getAuthType(): string
    {
        return (string)$this->getData(self::AUTH_TYPE);
    }

    public function setAuthType(string $authType): self
    {
        $this->setData(self::AUTH_TYPE, $authType);

        return $this;
    }

    public function getFieldName(): string
    {
        return (string)$this->getData(self::FIELD_NAME);
    }

    public function setFieldName(string $fieldName): self
    {
        $this->setData(self::FIELD_NAME, $fieldName);

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->getData(self::VALUE);
    }

    public function setValue(?string $value): self
    {
        $this->setData(self::VALUE, $value);

        return $this;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    public function setUpdatedAt(?string $updatedAt): self
    {
        $this->setData(self::UPDATED_AT, $updatedAt);

        return $this;
    }
}
