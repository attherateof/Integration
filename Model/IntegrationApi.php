<?php

declare(strict_types=1);

namespace MageStack\Integration\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use MageStack\Integration\Model\Api\Caller;
use MageStack\Integration\Model\Api\CacheManager;
use MageStack\Integration\Model\ApiAttemptRepository;
use MageStack\Integration\Api\Data\ApiAttemptInterface;
use MageStack\Integration\Model\ApiAttemptFactory;
use MageStack\Integration\Model\Config\Pool\RequestBuilderPool;
use MageStack\Integration\Model\Config\Pool\ResponseHandlerPool;
use MageStack\Integration\Model\Config\Pool\ResponseValidatorPool;
use MageStack\Integration\Model\Api\Retry\RetryScheduleCalculator;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;
use MageStack\Integration\Model\Exception\ApiException;

class IntegrationApi
{
    private ?string $apiCode = null;
    private ?string $endpointCode = null;
    private ?string $websiteCode = null;
    private array $payload = [];
    private array $urlParams = [];
    private int $attempt = 0;

    public function __construct(
        private readonly ConfigResolver $configResolver,
        private readonly RequestBuilderPool $requestBuilderPool,
        private readonly ResponseValidatorPool $responseValidatorPool,
        private readonly ResponseHandlerPool $responseHandlerPool,
        private readonly CacheManager $cacheManager,
        private readonly Caller $caller,
        private readonly ApiAttemptRepository $attemptRepository,
        private readonly ApiAttemptFactory $attemptFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger,
        private readonly RetryScheduleCalculator $scheduleCalculator
    ) {}

    public function setApi(string $apiCode): self
    {
        $this->apiCode = $apiCode;

        return $this;
    }

    public function getEndPoint(string $endpointCode): self
    {
        $this->endpointCode = $endpointCode;

        return $this;
    }

    public function setEndpoint(string $endpointCode): self
    {
        return $this->setEndPoint($endpointCode);
    }

    public function setWebsiteCode(string $websiteCode): self
    {
        $this->websiteCode = $websiteCode;

        return $this;
    }

    public function setUrlParams(array $urlParams): self
    {
        $this->urlParams = $urlParams;

        return $this;
    }

    public function setPayload(array $payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    public function setAttempt(int $attempt = 0): self
    {
        if ($attempt < 0) {
            throw new InvalidArgumentException('Attempt number cannot be negative.');
        }

        $this->attempt = $attempt;

        return $this;
    }

    public function trigger(): array
    {
        $this->validateState();

        $resolvedConfig = $this->configResolver->resolve($this->apiCode, $this->websiteCode);
        $endpointConfig = $this->getEndpointConfig($resolvedConfig);

        $websiteId = (int) $this->storeManager->getWebsite($this->websiteCode)->getId();

        [$requestPayload, $requestUrlParams, $requestHeaders] = $this->buildRequest($websiteId, $resolvedConfig, $endpointConfig);

        $cacheConfig = $endpointConfig['cache'] ?? ['enabled' => false];
        $cacheEnabled = $cacheConfig['enabled'] ?? false;
        $cacheKeyPrefix = $cacheConfig['key_prefix'] ?? '';
        $cacheTtl = $cacheConfig['ttl'] ?? null;

        if ($cacheEnabled) {
            $cached = $this->cacheManager->get(
                $cacheKeyPrefix,
                $this->apiCode,
                $this->endpointCode,
                $this->websiteCode,
                $requestPayload,
                $requestUrlParams
            );

            if ($cached !== false) {
                return $cached;
            }
        }
        // TODO:: Not now,in future, resolve and merge authentication header along with $requestHeaders, alse throw excepton
        $response = $this->executeCall($endpointConfig, $requestPayload, $requestUrlParams, $requestHeaders, $resolvedConfig);

        $this->handleRetryIfNeeded($response, $endpointConfig, $requestPayload, $requestUrlParams, $requestHeaders);

        $this->validateResponseIfNeeded($response, $endpointConfig);

        $response = $this->handleResponseIfNeeded($response, $endpointConfig);

        if ($cacheEnabled) {
            $statusCode = (int) ($response['status'] ?? 0);
            if ($statusCode >= 200 && $statusCode < 300) {
                $this->cacheManager->save(
                    $cacheKeyPrefix,
                    $this->apiCode,
                    $this->endpointCode,
                    $this->websiteCode,
                    $response,
                    $cacheTtl,
                    $requestPayload,
                    $requestUrlParams
                );
            }
        }

        return $response;
    }

    private function validateState(): void
    {
        if ($this->apiCode === null || $this->endpointCode === null || $this->websiteCode === null) {
            throw new InvalidArgumentException('Api code, endpoint, and website code must be set before triggering.');
        }
    }

    private function getEndpointConfig(array $resolvedConfig): array
    {
        $endpointConfig = $resolvedConfig['endpoints'][$this->endpointCode] ?? null;

        if (!is_array($endpointConfig)) {
            throw new LocalizedException(
                __('Endpoint "%1" is not configured for API "%2".', $this->endpointCode, $this->apiCode)
            );
        }

        if (isset($endpointConfig['enabled']) && $endpointConfig['enabled'] === false) {
            throw new LocalizedException(
                __('Endpoint "%1" for API "%2" is disabled.', $this->endpointCode, $this->apiCode)
            );
        }

        if (empty($endpointConfig['request_builder'] ?? null)) {
            throw new LocalizedException(
                __('Request builder is required for endpoint "%1".', $this->endpointCode)
            );
        }

        return $endpointConfig;
    }

    private function buildRequest(int $websiteId, array $resolvedConfig, array $endpointConfig): array
    {
        $requestBuilderCode = $endpointConfig['request_builder'];
        $requestBuilder = $this->requestBuilderPool->get($requestBuilderCode);
        $built = $requestBuilder->build($websiteId, $resolvedConfig, $this->payload, $this->urlParams);

        $requestPayload = $built['payload'] ?? [];
        $requestUrlParams = $built['urlParams'] ?? [];
        $requestHeaders = $built['headers'] ?? [];

        return [$requestPayload, $requestUrlParams, $requestHeaders];
    }

    private function executeCall(array $endpointConfig, array $requestPayload, array $requestUrlParams, array $requestHeaders, array $resolvedConfig): array
    {
        $url = $this->buildUrl($endpointConfig['path'] ?? '', $requestUrlParams);
        $method = strtoupper($endpointConfig['method'] ?? 'GET');
        $timeout = (int) ($endpointConfig['timeout'] ?? $resolvedConfig['timeout'] ?? 30);
        $headers = array_merge($this->payload['request_header'] ?? [], $requestHeaders);

        return $this->caller->call(
            $method,
            $url,
            $headers,
            $requestPayload,
            $timeout
        );
    }

    private function handleRetryIfNeeded(array $response, array $endpointConfig, array $requestPayload, array $requestUrlParams, array $requestHeaders): void
    {
        $retryConfig = $endpointConfig['retry'] ?? [];
        $retryEnabled = $retryConfig['enabled'] ?? false;
        $maxAttempts = (int) ($retryConfig['max_attempts'] ?? 1);
        $retryHttpCodes = $retryConfig['http_codes'] ?? [];

        $statusCode = (int) ($response['status'] ?? 0);

        if ($statusCode !== 0 && $retryEnabled && in_array($statusCode, $retryHttpCodes, true)) {
            if ($this->attempt < $maxAttempts) {
                $attemptModel = $this->attemptFactory->create();
                $attemptModel->setData([
                    ApiAttemptInterface::API_CODE => $this->apiCode,
                    ApiAttemptInterface::ENDPOINT_CODE => $this->endpointCode,
                    ApiAttemptInterface::WEBSITE_CODE => $this->websiteCode,
                    ApiAttemptInterface::PAYLOAD => $this->payload, // input in payload // convert to json string if needed
                    ApiAttemptInterface::URL_PARAMS => $this->urlParams,  //input in payload  convert to json string if needed
                    ApiAttemptInterface::ATTEMPT => $this->attempt,
                    ApiAttemptInterface::MAX_ATTEMPT => $maxAttempts,
                    ApiAttemptInterface::NEXT_ATTEMPT_AT => $this->scheduleCalculator->getNextRetryTime(
                        $this->attempt,
                        $retryConfig['backoff_multiplier'] ?? 2
                    )
                ]);

                $this->attemptRepository->save($attemptModel);
            } else {
                $this->logger->warning(
                    sprintf(
                        'API attempt exhausted for api=%s endpoint=%s website=%s attempt=%d status=%d',
                        $this->apiCode,
                        $this->endpointCode,
                        $this->websiteCode,
                        $this->attempt,
                        $statusCode
                    )
                );
            }
        }
    }

    private function validateResponseIfNeeded(array $response, array $endpointConfig): void
    {
        $validatorCode = $endpointConfig['response_validator'] ?? null;
        if ($validatorCode !== null) {
            $validator = $this->responseValidatorPool->get($validatorCode);

            if (!$validator->validate($response)) {
                throw new LocalizedException(
                    __('Response validation failed for endpoint "%1".', $this->endpointCode)
                );
            }
        }
    }

    private function handleResponseIfNeeded(array $response, array $endpointConfig): array
    {
        $handlerCode = $endpointConfig['response_handler'] ?? null;
        if ($handlerCode !== null) {
            $handler = $this->responseHandlerPool->get($handlerCode);
            return $handler->handle($response);
        }

        return $response;
    }

    private function buildUrl(string $path, array $urlParams): string
    {
        $url = rtrim($path, '/');

        if (empty($urlParams)) {
            return $url;
        }

        $query = http_build_query($urlParams);

        return $url . (strpos($url, '?') === false ? '?' : '&') . $query;
    }
}
