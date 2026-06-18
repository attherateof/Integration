<?php

declare(strict_types=1);

namespace MageStack\Integration\Api\Data;

interface CredentialInterface
{
    public const ID = 'credential_id';
    public const API_CODE = 'api_code';
    public const WEBSITE_ID = 'website_id';
    public const AUTH_TYPE = 'auth_type';
    public const FIELD_NAME = 'field_name';
    public const VALUE = 'field_value';
    public const UPDATED_AT = 'updated_at';

    public function getApiCode(): string;

    public function setApiCode(string $apiCode): self;

    public function getWebsiteId(): int;

    public function setWebsiteId(int $websiteId): self;

    public function getAuthType(): string;

    public function setAuthType(string $authType): self;

    public function getFieldName(): string;

    public function setFieldName(string $fieldName): self;

    public function getValue(): ?string;

    public function setValue(?string $value): self;

    public function getUpdatedAt(): ?string;

    public function setUpdatedAt(?string $updatedAt): self;
}
