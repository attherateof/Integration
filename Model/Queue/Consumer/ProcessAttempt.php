<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Queue\Consumer;

use Magento\Framework\Exception\NoSuchEntityException;
use MageStack\Integration\Model\ApiAttemptRepository;
use MageStack\Integration\Model\Service\Integration\IntegrationBuilderFactory;
use MageStack\Integration\Model\Service\Integration\IntegrationManager;
use MageStack\Integration\Model\Service\ResolvedConfigProvider;
use Psr\Log\LoggerInterface;
use Throwable;

class ProcessAttempt
{
    public function __construct(
        private readonly ResolvedConfigProvider $resolvedConfigProvider,
        private readonly IntegrationBuilderFactory $integrationBuilderFactory,
        private readonly ApiAttemptRepository $attemptRepository,
        private readonly IntegrationManager $integrationManager,
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

        $attemptId = (int) $message['attempt_id'];

        try {
            if (
                isset($message['attempt'], $message['max_attempt']) &&
                $message['attempt'] >= $message['max_attempt']
            ) {
                $this->logger->info(
                    sprintf(
                        'ProcessAttempt exhausted for attempt id=%d after %d attempts. Removing.',
                        $attemptId,
                        $message['attempt']
                    )
                );

                /** 
                 * Do not delete the attempt here, as it may be needed for logging or auditing purposes. 
                 * The system can handle cleanup later.
                 */

                return;
            }

            $success = $this->processMessage($message);

            if ($success) {
                $this->logger->info(sprintf('Deleted attempt id=%d after successful processing.', $attemptId));
                $this->deleteAttempt($attemptId);
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->warning('Attempt not found while processing: ' . $e->getMessage());
        } catch (Throwable $e) {
            $this->logger->error('Error processing attempt payload: ' . $e->getMessage());
        }
    }

    private function deleteAttempt(int $attemptId): void
    {
        $attempt = $this->attemptRepository->getById($attemptId);
        $this->attemptRepository->delete($attempt);
    }

    private function processMessage(array $message): bool
    {
        $apiCode = $message['api_code'] ?? null;
        $endpointCode = $message['endpoint_code'] ?? null;
        $websiteCode = $message['website_code'] ?? null;
        $callData = $message['payload'] ?? [];
        $callUrlParams = $message['url_params'] ?? [];
        $attemptNumber = (int) ($message['attempt'] ?? 0) + 1;

        if (empty($apiCode) || empty($endpointCode) || empty($websiteCode)) {
            $this->logger->warning('ProcessAttempt payload missing required api_code, endpoint_code or website_code.');
            return false;
        }

        try {
            $integrationBuilder = $this->integrationBuilderFactory->create();

            $integrationBuilder->setApiCode($apiCode)
                ->setEndpointCode($endpointCode)
                ->setWebsiteCode($websiteCode)
                ->setData(is_array($callData) ? $callData : [])
                ->setUrlParams(is_array($callUrlParams) ? $callUrlParams : [])
                ->setAttempt($attemptNumber);

            $response = $this->integrationManager->trigger($integrationBuilder);

            $statusCode = (int) ($response['status'] ?? 0);

            $retryEnabled = $this->resolvedConfigProvider->isEndpointRetryEnabled($apiCode, $endpointCode, $websiteCode);
            if (!$retryEnabled) {
                $this->logger->info(sprintf('Retry disabled for api=%s endpoint=%s website=%s. Treating as success.', $apiCode, $endpointCode, $websiteCode));
                return true;
            }

            $retryHttpCodes = $this->resolvedConfigProvider->getEndpointHttpCodes($apiCode, $endpointCode, $websiteCode);
            if (in_array($statusCode, $retryHttpCodes, true)) {
                $this->logger->info(sprintf('API call failed with status %d for api=%s endpoint=%s website=%s. Will retry.', $statusCode, $apiCode, $endpointCode, $websiteCode));
                return false;
            }

            return true;
        } catch (Throwable $e) {
            $this->logger->error('IntegrationApi processing failed: ' . $e->getMessage());
            return false;
        }
    }
}
