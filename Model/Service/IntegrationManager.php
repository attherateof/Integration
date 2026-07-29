<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Service;

use InvalidArgumentException;
use Magento\Store\Model\StoreManagerInterface;
use MageStack\Integration\Model\Service\Integration\RequestAssembler;
use MageStack\Integration\Model\Service\Integration\EndpointCache;
use MageStack\Integration\Model\Service\Integration\HttpCallExecutor;
use MageStack\Integration\Model\Service\Integration\RetryHandler;
use MageStack\Integration\Model\Service\Integration\ResponseProcessor;
use MageStack\Integration\Model\Service\Integration\IntegrationBuilder;
use MageStack\Integration\Model\Service\Integration\AuthHeaderProvider;

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
        private readonly ResponseProcessor $responseProcessor,
        private readonly AuthHeaderProvider $authHeaderProvider
    ) {}

    public function trigger(IntegrationBuilder $request): array
    {
        $this->validateState($request);

        $apiCode = $request->getApiCode();
        $endpointCode = $request->getEndpointCode();
        $websiteCode = $request->getWebsiteCode();
        $payload = $request->getData();

        [$headers, $body, $urlParams] = $this->requestAssembler->assemble($request);

        $cacheEnabled = $this->endpointCache->isEnabled($apiCode, $endpointCode, $websiteCode);

        if ($cacheEnabled) {
            $cached = $this->endpointCache->fetch($apiCode, $endpointCode, $websiteCode, $body, $urlParams);

            if ($cached !== false) {
                return $cached;
            }
        }

        $headers = $this->authHeaderProvider->get(
            $request,
            $headers
        );

        $response = $this->httpCallExecutor->execute(
            $apiCode,
            $endpointCode,
            $websiteCode,
            $body,
            $urlParams,
            $headers,
            $payload
        );

        $this->retryHandler->handleIfNeeded($response, $request);

        $response = $this->responseProcessor->process($response, $apiCode, $endpointCode, $websiteCode);

        if ($cacheEnabled) {
            $this->endpointCache->store($apiCode, $endpointCode, $websiteCode, $response, $payload, $urlParams);
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
