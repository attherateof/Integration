<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Queue\Consumer;

use Magento\Framework\Exception\NoSuchEntityException;
use MageStack\Integration\Model\ApiAttemptRepository;
use MageStack\Integration\Model\Service\Integration\IntegrationBuilderFactory;
use MageStack\Integration\Model\Service\Integration\IntegrationManager;
use MageStack\Integration\Model\ConfigResolver;
use Psr\Log\LoggerInterface;

class ProcessAttempt
{
    public function __construct(
        private readonly IntegrationBuilderFactory $integrationBuilderFactory,
        private readonly ApiAttemptRepository $attemptRepository,
        private readonly IntegrationManager $integrationManager,
        private readonly ConfigResolver $configResolver,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Message payload expected: either
     * - a processing payload with no success key, or
     * - a result payload with success and next_attempt_at.
     */
    public function process(array $message): void
    {
        if (empty($message) || !isset($message['attempt_id'])) {
            $this->logger->warning('ProcessAttempt received invalid message payload.');
            return;
        }

        try {
            if (
                isset($message['attempt'], $message['max_attempt']) &&
                $message['attempt'] >= $message['max_attempt']
            ) {
                $this->logger->info(sprintf('ProcessAttempt received result payload for attempt id=%d: success=%s', $message['attempt_id'], $message['success'] ? 'true' : 'false'));
                return;
            }

            $success = $this->processPayload($message);

            $attempt = $this->attemptRepository->getById((int) $message['attempt_id']);

            if ($success) {
                $this->attemptRepository->delete($attempt);
                $this->logger->info(sprintf('Deleted attempt id=%d after successful processing.', $attempt->getId()));
                return;
            }

            $attempt->setAttempt($attempt->getAttempt() + 1);
            $minutes = (int) pow(2, $attempt->getAttempt());
            $next = (new \DateTimeImmutable('now'))->add(new \DateInterval('PT' . $minutes . 'M'));
            $attempt->setNextAttemptAt($next->format('Y-m-d H:i:s'));
            $this->attemptRepository->save($attempt);
            $this->logger->info(sprintf('Updated attempt id=%d for retry.', $attempt->getId()));
        } catch (NoSuchEntityException $e) {
            $this->logger->warning('Attempt not found while processing: ' . $e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->error('Error processing attempt payload: ' . $e->getMessage());
        }
    }

    private function processPayload(array $payload): bool
    {
        $apiCode = $payload['api_code'] ?? null;
        $endpointCode = $payload['endpoint_code'] ?? null;
        $websiteCode = $payload['website_code'] ?? null;
        $callPayload = $payload['payload'] ?? [];
        $callUrlParams = $payload['url_params'] ?? [];
        $attemptNumber = (int) ($payload['attempt'] ?? 0) + 1;

        if (empty($apiCode) || empty($endpointCode) || empty($websiteCode)) {
            $this->logger->warning('ProcessAttempt payload missing required api_code, endpoint_code or website_code.');
            return false;
        }

        try {
            $integrationBuilder = $this->integrationBuilderFactory->create();

            $integrationBuilder->setApi($apiCode)
                ->setEndpoint($endpointCode)
                ->setWebsiteCode($websiteCode)
                ->setData(is_array($callPayload) ? $callPayload : [])
                ->setUrlParams(is_array($callUrlParams) ? $callUrlParams : [])
                ->setHeaders([])
                ->setAttempt($attemptNumber);

            $response = $this->integrationManager->trigger($integrationBuilder);

            $statusCode = (int) ($response['status'] ?? 0);


            // TODO::to get retry config should have a separate reusable class
            $resolvedConfig = $this->configResolver->resolve($apiCode, $websiteCode);
            $endpointConfig = $resolvedConfig['endpoints'][$endpointCode] ?? null;
            if ($endpointConfig === null) {
                $this->logger->warning(sprintf('Endpoint configuration not found for api=%s endpoint=%s website=%s.', $apiCode, $endpointCode, $websiteCode));
                return false;
            }

            $retryConfig = $endpointConfig['retry'] ?? null;

            if ($retryConfig === null) {
                $this->logger->info(sprintf('No retry configuration for api=%s endpoint=%s website=%s. Treating as success.', $apiCode, $endpointCode, $websiteCode));
                return true;
            }

            $retryEnabled = $retryConfig['enabled'] ?? false;

            if (!$retryEnabled) {
                $this->logger->info(sprintf('Retry disabled for api=%s endpoint=%s website=%s. Treating as success.', $apiCode, $endpointCode, $websiteCode));
                return true;
            }

            $retryHttpCodes = $retryConfig['http_codes'] ?? [];
            if ($statusCode !== 0 && $retryEnabled && in_array($statusCode, $retryHttpCodes, true)) {
                $this->logger->info(sprintf('API call failed with status %d for api=%s endpoint=%s website=%s. Will retry.', $statusCode, $apiCode, $endpointCode, $websiteCode));
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('IntegrationApi processing failed: ' . $e->getMessage());
            return false;
        }
    }
}
