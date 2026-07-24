<?php

declare(strict_types=1);

namespace MageStack\Integration\Api\Data;

interface ApiAttemptInterface
{
    public const ATTEMPT_ID = 'attempt_id';
    public const API_CODE = 'api_code';
    public const ENDPOINT_CODE = 'endpoint_code';
    public const WEBSITE_CODE = 'website_code';
    public const DATA = 'data'; // This is the input payload for the API request
    public const URL_PARAMS = 'url_params';
    public const HEADERS = 'headers';
    public const ATTEMPT = 'attempt';
    public const MAX_ATTEMPT = 'max_attempt';
    public const NEXT_ATTEMPT_AT = 'next_attempt_at';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public function getId(): ?int;

    public function getApiCode(): ?string;
    public function setApiCode(string $code): self;

    public function getEndpointCode(): ?string;
    public function setEndpointCode(string $code): self;

    public function getWebsiteCode(): ?string;
    public function setWebsiteCode(string $code): self;

    public function getInputData(): ?array;
    public function setInputData(array $data = []): self;

    public function getUrlParams(): ?array;
    public function setUrlParams(array $params = []): self;

    public function getHeaders(): ?array;
    public function setHeaders(array $headers = []): self;

    public function getAttempt(): int;
    public function setAttempt(int $attempt): self;

    public function getMaxAttempt(): int;
    public function setMaxAttempt(int $maxAttempt): self;

    public function getNextAttemptAt(): ?string;
    public function setNextAttemptAt(?string $date): self;

    public function getCreatedAt(): ?string;
    public function setCreatedAt(string $date): self;

    public function getUpdatedAt(): ?string;
    public function setUpdatedAt(string $date): self;
}
