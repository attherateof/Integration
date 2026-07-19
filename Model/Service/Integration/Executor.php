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
use MageStack\Integration\Model\Service\Integration\RequestBuilder;

class IntegrationExecutor
{
    public function __construct(
        private readonly RequestValidator $requestValidator,
        private readonly ConfigResolver $configResolver,
        private readonly StoreManagerInterface $storeManager,
        private readonly RequestBuilderPool $requestBuilderPool,
        private readonly AuthenticationPool $authenticationPool,
        private readonly CacheManager $cacheManager,
        private readonly Caller $caller,
        private readonly RetryManagerInterface $retryManager,
        private readonly ResponseValidatorPool $responseValidatorPool,
        private readonly ResponseHandlerPool $responseHandlerPool
    ) {}

    public function execute(RequestBuilder $request): array
    {
        $this->requestValidator->validate($request);

        $config = $this->configResolver->resolve(
            $request->getApiCode(),
            $request->getWebsiteCode()
        );

        $endpoint = $this->getEndpointConfig(
            $config,
            $request->getEndpointCode()
        );

        $apiRequest = $this->buildRequest(
            $request,
            $config,
            $endpoint
        );

        $apiRequest = $this->authenticate(
            $apiRequest,
            $endpoint,
            $config
        );

        if ($cached = $this->loadFromCache($apiRequest, $endpoint)) {
            return $cached;
        }

        $response = $this->caller->call($apiRequest);

        $this->retryManager->process(
            $request,
            $response,
            $endpoint
        );

        $this->validateResponse(
            $response,
            $endpoint
        );

        $response = $this->handleResponse(
            $response,
            $endpoint
        );

        $this->saveCache(
            $apiRequest,
            $response,
            $endpoint
        );

        return $response;
    }
}
