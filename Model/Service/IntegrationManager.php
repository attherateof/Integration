<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Service\Integration;

use InvalidArgumentException;
use Magento\Store\Model\StoreManagerInterface;
use MageStack\Integration\Model\Service\Integration\RequestAssembler;
use MageStack\Integration\Model\Service\Integration\EndpointCache;
use MageStack\Integration\Model\Service\Integration\HttpCallExecutor;
use MageStack\Integration\Model\Service\Integration\RetryHandler;
use MageStack\Integration\Model\Service\Integration\ResponseProcessor;

/**
 * Orchestrates a single API trigger by sequencing request assembly,
 * caching, the HTTP call, retry scheduling, and response processing.
 * Each of those concerns is delegated to a dedicated collaborator so this
 * class only has one job: the sequence, not the mechanics of any one step.
 */
class IntegrationManager
{
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly RequestAssembler $requestAssembler,
        private readonly EndpointCache $endpointCache,
        private readonly HttpCallExecutor $httpCallExecutor,
        private readonly RetryHandler $retryHandler,
        private readonly ResponseProcessor $responseProcessor
    ) {}

    public function trigger(IntegrationBuilder $request): array
    {
        $this->validateState($request);

        $apiCode = $request->getApiCode();
        $endpointCode = $request->getEndpointCode();
        $websiteCode = $request->getWebsiteCode();
        $payload = $request->getData();
        $urlParams = $request->getUrlParams();

        $websiteId = (int) $this->storeManager->getWebsite($websiteCode)->getId();

        [$requestPayload, $requestUrlParams, $requestHeaders] = $this->requestAssembler->assemble(
            $websiteId,
            $apiCode,
            $endpointCode,
            $websiteCode,
            $payload,
            $urlParams
        );

        $cacheEnabled = $this->endpointCache->isEnabled($apiCode, $endpointCode, $websiteCode);

        if ($cacheEnabled) {
            $cached = $this->endpointCache->fetch($apiCode, $endpointCode, $websiteCode, $requestPayload, $requestUrlParams);

            if ($cached !== false) {
                return $cached;
            }
        }

        // TODO:: Not now, in future, resolve and merge authentication header along with $requestHeaders, also throw exception in case of error
        $response = $this->httpCallExecutor->execute(
            $apiCode,
            $endpointCode,
            $websiteCode,
            $requestPayload,
            $requestUrlParams,
            $requestHeaders,
            $payload
        );

        $this->retryHandler->handleIfNeeded($response, $request);

        $response = $this->responseProcessor->process($response, $apiCode, $endpointCode, $websiteCode);

        if ($cacheEnabled) {
            $this->endpointCache->store($apiCode, $endpointCode, $websiteCode, $response, $requestPayload, $requestUrlParams);
        }

        return $response;
    }

    private function validateState(IntegrationBuilder $request): void
    {
        if (
            $request->getApiCode() === null
            || $request->getEndpointCode() === null
            || $request->getWebsiteCode() === null
        ) {
            throw new InvalidArgumentException('Api code, endpoint, and website code must be set before triggering.');
        }
    }
}
