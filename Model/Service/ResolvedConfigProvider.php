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

    public function __construct(
        private readonly ConfigResolver $configResolver,
        private readonly RequestBuilderPool $requestBuilderPool,
        private readonly ResponseValidatorPool $responseValidatorPool,
        private readonly ResponseHandlerPool $responseHandlerPool,
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

        if (!($this->configCache[$key] ?? false)) {
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
            $timeout = $minTimeout;
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

        if ($prefix !== null && $prefix !== '') {
            return $apiCode . '_' . $endpointCode . '_' . $websiteCode . '_' . (string)$prefix;
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
        $defaultMaxAttempts = 0;

        if ($this->isEndpointRetryEnabled($apiCode, $endpointCode, $websiteCode) === false) {

            return $defaultMaxAttempts;
        }

        $maxAttempts = (int)$this->getEndpointNestedValue(
            $apiCode,
            $endpointCode,
            $websiteCode,
            'retry',
            'max_attempts',
            $defaultMaxAttempts
        );

        if ($maxAttempts < $defaultMaxAttempts) {
            $maxAttempts = $defaultMaxAttempts;
        }

        return $maxAttempts;
    }

    public function getEndpointRetryBackoffMultiplier(
        string $apiCode,
        string $endpointCode,
        string $websiteCode
    ): int {
        $defaultBackoffMultiplier = 1;
        if ($this->isEndpointRetryEnabled($apiCode, $endpointCode, $websiteCode) === false) {

            return $defaultBackoffMultiplier;
        }
        $backoffMultiplier = (int)$this->getEndpointNestedValue(
            $apiCode,
            $endpointCode,
            $websiteCode,
            'retry',
            'backoff_multiplier',
            $defaultBackoffMultiplier
        );

        if ($backoffMultiplier < $defaultBackoffMultiplier) {
            $backoffMultiplier = $defaultBackoffMultiplier;
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
            if ($code < 100 || $code > 599) {
                throw new LocalizedException(
                    __(
                        'Invalid HTTP status code "%1" configured for endpoint "%2".',
                        $code,
                        $endpointCode
                    )
                );
            }
        }

        return $codes;
    }

    public function getEndpointRequestBuilder(
        string $apiCode,
        string $endpointCode,
        string $websiteCode
    ): RequestBuilderInterface {
        $requestBuilderIdentifier = (string)$this->getEndpointValue(
            $apiCode,
            $endpointCode,
            $websiteCode,
            'request_builder'
        );

        if ($requestBuilderIdentifier === '' || $requestBuilderIdentifier == null) {
            throw new LocalizedException(
                __(
                    'Request builder identifier for "%1" is missing required configuration "%2".',
                    $endpointCode,
                    $apiCode
                )
            );
        }

        return $this->requestBuilderPool->get($requestBuilderIdentifier);
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

        if ($validatorIdentifier) {
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

        if ($handlerIdentifier) {
            return $this->responseHandlerPool->get((string)$handlerIdentifier);
        }

        return null;
    }
}
