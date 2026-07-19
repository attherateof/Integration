<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Service\Integration;

use MageStack\Integration\Api\Data\ApiAttemptInterface;
use MageStack\Integration\Model\Api\Retry\RetryScheduleCalculator;
use MageStack\Integration\Model\ApiAttemptFactory;
use MageStack\Integration\Model\ApiAttemptRepository;
use MageStack\Integration\Model\Service\ResolvedConfigProvider;
use Psr\Log\LoggerInterface;

/**
 * Decides whether a response should be retried per the endpoint's retry
 * config, and either schedules the next attempt or logs exhaustion.
 */
class RetryHandler
{
    public function __construct(
        private readonly ResolvedConfigProvider $resolvedConfigProvider,
        private readonly ApiAttemptRepository $attemptRepository,
        private readonly ApiAttemptFactory $attemptFactory,
        private readonly RetryScheduleCalculator $scheduleCalculator,
        private readonly LoggerInterface $logger
    ) {}

    public function handleIfNeeded(array $response, IntegrationBuilder $request): void
    {

        $apiCode = $request->getApiCode();
        $endpointCode = $request->getEndpointCode();
        $websiteCode = $request->getWebsiteCode();


        $retryEnabled = $this->resolvedConfigProvider->isEndpointRetryEnabled($apiCode, $endpointCode, $websiteCode);
        $statusCode = (int) ($response['status'] ?? 0);

        if ($statusCode === 0 || !$retryEnabled) {
            return;
        }

        $retryHttpCodes = $this->resolvedConfigProvider->getEndpointHttpCodes($apiCode, $endpointCode, $websiteCode);

        if (!in_array($statusCode, $retryHttpCodes, true)) {
            return;
        }

        $maxAttempts = $this->resolvedConfigProvider->getEndpointRetryMaxAttempts($apiCode, $endpointCode, $websiteCode);

        if ($attempt = $request->getAttempt() < $maxAttempts) {
            $payload = $request->getData();
            $urlParams = $request->getUrlParams();
            $this->scheduleNextAttempt(
                $apiCode,
                $endpointCode,
                $websiteCode,
                $payload,
                $urlParams,
                $attempt,
                $maxAttempts
            );

            return;
        }

        $this->logger->warning(
            sprintf(
                'API attempt exhausted for api=%s endpoint=%s website=%s attempt=%d status=%d',
                $apiCode,
                $endpointCode,
                $websiteCode,
                $attempt,
                $statusCode
            )
        );
    }

    private function scheduleNextAttempt(
        string $apiCode,
        string $endpointCode,
        string $websiteCode,
        array $payload,
        array $urlParams,
        int $attempt,
        int $maxAttempts
    ): void {
        $backoffMultiplier = $this->resolvedConfigProvider->getEndpointRetryBackoffMultiplier($apiCode, $endpointCode, $websiteCode);

        $attemptModel = $this->attemptFactory->create();
        $attemptModel->setData([
            ApiAttemptInterface::API_CODE => $apiCode,
            ApiAttemptInterface::ENDPOINT_CODE => $endpointCode,
            ApiAttemptInterface::WEBSITE_CODE => $websiteCode,
            ApiAttemptInterface::PAYLOAD => $payload, // input in payload // convert to json string if needed
            ApiAttemptInterface::URL_PARAMS => $urlParams,  //input in payload  convert to json string if needed
            ApiAttemptInterface::ATTEMPT => $attempt,
            ApiAttemptInterface::MAX_ATTEMPT => $maxAttempts,
            ApiAttemptInterface::NEXT_ATTEMPT_AT => $this->scheduleCalculator->getNextRetryTime(
                $attempt,
                $backoffMultiplier
            )
        ]);

        $this->attemptRepository->save($attemptModel);
    }
}
