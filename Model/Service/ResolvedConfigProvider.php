<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Service;

use Magento\Framework\Exception\LocalizedException;
use MageStack\Integration\Model\ConfigResolver;
use MageStack\Integration\Model\Config\Pool\RequestBuilderPool;
use MageStack\Integration\Model\Config\Pool\ResponseHandlerPool;
use MageStack\Integration\Model\Config\Pool\ResponseValidatorPool;
use MageStack\Integration\Api\Config\RequestBuilderInterface;
use MageStack\Integration\Api\Config\ResponseValidatorInterface;
use MageStack\Integration\Api\Config\ResponseHandlerInterface;
use MageStack\Integration\Model\Auth\AuthenticationPool;

class ResolvedConfigProvider
{
    private array $configCache = [];

    private const HTTP_METHODS = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'HEAD',
        'OPTIONS'
    ];

    private const VALID_HTTP_CODE_START_FROM = 100;

    private const VALID_HTTP_CODE_END_AT = 599;

    private const DEFAULT_BACKOFF_MULTIPLIER = 1;

    private const DEFAULT_MAX_ATTEMPTS = 0;

    private const TOKEN_TTL = 3600;

    public function __construct(
        private readonly ConfigResolver $configResolver,
        private readonly RequestBuilderPool $requestBuilderPool,
        private readonly ResponseValidatorPool $responseValidatorPool,
        private readonly ResponseHandlerPool $responseHandlerPool,
        private readonly AuthenticationPool $authenticationPool
    ) {}

    /**
     * Returns the fully resolved API configuration.
     *
     * @throws LocalizedException
     */
    private function getApiConfig(
        string $apiCode,
        string $websiteCode
    ): array {
        $key = $apiCode . '_' . $websiteCode;

        if (!isset($this->configCache[$key])) {
            $this->configCache[$key] = $this->configResolver->resolve(
                $apiCode,
                $websiteCode
            );
        }

        if (!($this->configCache[$key]['enabled'] ?? false)) {
            throw new LocalizedException(
                __('API "%1" is disabled.', $apiCode)
            );
        }

        return $this->configCache[$key];
    }

    /**
     * Returns the authentication configuration.
     *
     * @throws LocalizedException
     */
    private function getAuthenticationConfig(
        string $apiCode,
        string $websiteCode
    ): array {
        $config = $this->getApiConfig(
            $apiCode,
            $websiteCode
        );

        if (!isset($config['authentication'])) {
            throw new LocalizedException(
                __('Authentication is not configured for API "%1".', $apiCode)
            );
        }

        return $config['authentication'];
    }

    private function getAuthenticationValue(
        string $apiCode,
        string $websiteCode,
        string $key,
        mixed $default = null
    ): mixed {
        $authentication = $this->getAuthenticationConfig(
            $apiCode,
            $websiteCode
        );

        return $authentication[$key] ?? $default;
    }

    /**
     * Returns endpoint configuration.
     *
     * @throws LocalizedException
     */
    private function getEndpointConfig(
        string $apiCode,
        string $endpointCode,
        string $websiteCode
    ): array {
        $config = $this->getApiConfig(
            $apiCode,
            $websiteCode
        );

        $endpoint = $config['endpoints'][$endpointCode] ?? null;

        if (!is_array($endpoint)) {
            throw new LocalizedException(
                __(
                    'Endpoint "%1" is not configured for API "%2".',
                    $endpointCode,
                    $apiCode
                )
            );
        }

        if (!($endpoint['enabled'] ?? false)) {
            throw new LocalizedException(
                __(
                    'Endpoint "%1" for API "%2" is disabled.',
                    $endpointCode,
                    $apiCode
                )
            );
        }

        return $endpoint;
    }

    private function getEndpointValue(
        string $apiCode,
        string $endpointCode,
        string $websiteCode,
        string $key,
        mixed $default = null
    ): mixed {
        $endpoint = $this->getEndpointConfig(
            $apiCode,
            $endpointCode,
            $websiteCode
        );

        return $endpoint[$key] ?? $default;
    }

    private function getEndpointNestedValue(
        string $apiCode,
        string $endpointCode,
        string $websiteCode,
        string $section,
        string $key,
        mixed $default = null
    ): mixed {
        $endpoint = $this->getEndpointConfig(
            $apiCode,
            $endpointCode,
            $websiteCode
        );

        return $endpoint[$section][$key] ?? $default;
    }

    public function getEndpointMethod(
        string $apiCode,
        string $endpointCode,
        string $websiteCode
    ): string {

        $method = strtoupper((string)$this->getEndpointValue(
            $apiCode,
            $endpointCode,
            $websiteCode,
            'method'
        ));

        if (!in_array($method, self::HTTP_METHODS, true)) {
            throw new LocalizedException(
                __(
                    'Invalid HTTP method "%1" configured for endpoint "%2".',
                    $method,
                    $endpointCode
                )
            );
        }

        return $method;
    }

    public function getEndpointPath(
        string $apiCode,
        string $endpointCode,
        string $websiteCode
    ): string {
        $path = (string)$this->getEndpointValue(
            $apiCode,
            $endpointCode,
            $websiteCode,
            'path'
        );

        if ($path === '') {
            throw new LocalizedException(
                __(
                    'Endpoint path "%1" is missing required configuration "%2".',
                    $endpointCode,
                    $apiCode
                )
            );
        }

        return $path;
    }

    public function getEndpointTimeout(
        string $apiCode,
        string $endpointCode,
        string $websiteCode
    ): int {
        $minTimeout = 30;

        $timeout = (int)$this->getEndpointValue(
            $apiCode,
            $endpointCode,
            $websiteCode,
            'timeout',
            $minTimeout
        );

        if ($timeout <= $minTimeout) {
            throw new LocalizedException(
                __(
                    'Invalid timeout "%1" configured for endpoint "%2". Must be greater than %3 seconds.',
                    $timeout,
                    $endpointCode,
                    $minTimeout
                )
            );
        }

        return $timeout;
    }

    public function isEndpointCacheEnabled(
        string $apiCode,
        string $endpointCode,
        string $websiteCode
    ): bool {
        return (bool)$this->getEndpointNestedValue(
            $apiCode,
            $endpointCode,
            $websiteCode,
            'cache',
            'enabled',
            false
        );
    }

    public function getEndpointCacheTtl(
        string $apiCode,
        string $endpointCode,
        string $websiteCode
    ): ?int {

        if ($this->isEndpointCacheEnabled($apiCode, $endpointCode, $websiteCode) === false) {

            return null;
        }

        $ttl = $this->getEndpointNestedValue(
            $apiCode,
            $endpointCode,
            $websiteCode,
            'cache',
            'ttl'
        );

        return $ttl !== null ? (int)$ttl : null;
    }

    public function getEndpointCacheKey(
        string $apiCode,
        string $endpointCode,
        string $websiteCode
    ): ?string {
        if ($this->isEndpointCacheEnabled($apiCode, $endpointCode, $websiteCode) === false) {

            return null;
        }

        $prefix = $this->getEndpointNestedValue(
            $apiCode,
            $endpointCode,
            $websiteCode,
            'cache',
            'key_prefix'
        );

        if (is_string($prefix) && $prefix !== '') {
            return (string)$prefix;
        }

        return null;
    }

    public function isEndpointRetryEnabled(
        string $apiCode,
        string $endpointCode,
        string $websiteCode
    ): bool {
        return (bool)$this->getEndpointNestedValue(
            $apiCode,
            $endpointCode,
            $websiteCode,
            'retry',
            'enabled',
            false
        );
    }

    public function getEndpointRetryMaxAttempts(
        string $apiCode,
        string $endpointCode,
        string $websiteCode
    ): int {

        if ($this->isEndpointRetryEnabled($apiCode, $endpointCode, $websiteCode) === false) {

            return self::DEFAULT_MAX_ATTEMPTS;
        }

        $maxAttempts = (int)$this->getEndpointNestedValue(
            $apiCode,
            $endpointCode,
            $websiteCode,
            'retry',
            'max_attempts',
            self::DEFAULT_MAX_ATTEMPTS
        );

        if ($maxAttempts < self::DEFAULT_MAX_ATTEMPTS) {
            throw new LocalizedException(
                __(
                    'Invalid max attempts "%1" configured for endpoint "%2". Must be greater than or equal to %3.',
                    $maxAttempts,
                    $endpointCode,
                    self::DEFAULT_MAX_ATTEMPTS
                )
            );
        }

        return $maxAttempts;
    }

    public function getEndpointRetryBackoffMultiplier(
        string $apiCode,
        string $endpointCode,
        string $websiteCode
    ): int {
        if ($this->isEndpointRetryEnabled($apiCode, $endpointCode, $websiteCode) === false) {

            return self::DEFAULT_BACKOFF_MULTIPLIER;
        }
        $backoffMultiplier = (int)$this->getEndpointNestedValue(
            $apiCode,
            $endpointCode,
            $websiteCode,
            'retry',
            'backoff_multiplier',
            self::DEFAULT_BACKOFF_MULTIPLIER
        );

        if ($backoffMultiplier < self::DEFAULT_BACKOFF_MULTIPLIER) {
            throw new LocalizedException(
                __(
                    'Invalid backoff multiplier "%1" configured for endpoint "%2". Must be greater than or equal to %3.',
                    $backoffMultiplier,
                    $endpointCode,
                    self::DEFAULT_BACKOFF_MULTIPLIER
                )
            );
        }

        return $backoffMultiplier;
    }

    public function getEndpointHttpCodes(
        string $apiCode,
        string $endpointCode,
        string $websiteCode
    ): array {
        $codes = (array)$this->getEndpointNestedValue(
            $apiCode,
            $endpointCode,
            $websiteCode,
            'retry',
            'http_codes',
            []
        );

        $codes = array_map('intval', $codes);
        foreach ($codes as $code) {
            if ($code < self::VALID_HTTP_CODE_START_FROM || $code > self::VALID_HTTP_CODE_END_AT) {
                throw new LocalizedException(
                    __(
                        'Invalid HTTP status code "%1" configured for endpoint "%2".',
                        $code,
                        $endpointCode
                    )
                );
            }
        }

        return array_values(array_unique($codes));
    }

    public function getEndpointRequestBuilder(
        string $apiCode,
        string $endpointCode,
        string $websiteCode
    ): RequestBuilderInterface {
        $requestBuilderIdentifier = $this->getEndpointValue(
            $apiCode,
            $endpointCode,
            $websiteCode,
            'request_builder'
        );

        if (!is_string($requestBuilderIdentifier)) {
            throw new LocalizedException(
                __(
                    'Request builder identifier for "%1" is missing required configuration "%2".',
                    $endpointCode,
                    $apiCode
                )
            );
        }

        return $this->requestBuilderPool->get((string)$requestBuilderIdentifier);
    }

    public function getEndpointResponseValidator(
        string $apiCode,
        string $endpointCode,
        string $websiteCode
    ): ?ResponseValidatorInterface {
        $validatorIdentifier = $this->getEndpointValue(
            $apiCode,
            $endpointCode,
            $websiteCode,
            'response_validator'
        );

        if (is_string($validatorIdentifier) && $validatorIdentifier !== '') {
            return $this->responseValidatorPool->get((string)$validatorIdentifier);
        }

        return null;
    }

    public function getEndpointResponseHandler(
        string $apiCode,
        string $endpointCode,
        string $websiteCode
    ): ?ResponseHandlerInterface {
        $handlerIdentifier = $this->getEndpointValue(
            $apiCode,
            $endpointCode,
            $websiteCode,
            'response_handler'
        );

        if (is_string($handlerIdentifier) && $handlerIdentifier !== '') {
            return $this->responseHandlerPool->get((string)$handlerIdentifier);
        }

        return null;
    }

    public function getAuthenticationType(
        string $apiCode,
        string $websiteCode
    ): string {
        $type = strtolower((string)$this->getAuthenticationValue(
            $apiCode,
            $websiteCode,
            'type'
        ));

        $supportedTypes = $this->authenticationPool->supportedTypes();

        if (!in_array($type, $supportedTypes, true)) {
            throw new LocalizedException(
                __(
                    'Unsupported authentication type "%1" configured for API "%2".',
                    $type,
                    $apiCode
                )
            );
        }

        return $type;
    }

    public function getTokenEndpoint(
        string $apiCode,
        string $websiteCode
    ): string {
        $endpoint = $this->getAuthenticationValue(
            $apiCode,
            $websiteCode,
            'token_endpoint'
        );

        if (!is_string($endpoint) || $endpoint === '') {
            throw new LocalizedException(
                __(
                    'Token endpoint is not configured for API "%1".',
                    $apiCode
                )
            );
        }

        return $endpoint;
    }

    public function getTokenTtl(
        string $apiCode,
        string $websiteCode
    ): int {
        $ttl = (int)$this->getAuthenticationValue(
            $apiCode,
            $websiteCode,
            'token_ttl',
            self::TOKEN_TTL
        );

        if ($ttl <= 0) {
            throw new LocalizedException(
                __(
                    'Invalid token TTL "%1" configured for API "%2".',
                    $ttl,
                    $apiCode
                )
            );
        }

        return $ttl;
    }
}
