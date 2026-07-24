<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Cron;

use MageStack\Integration\Model\AdminConfig;
use MageStack\Integration\Model\ApiAttemptRepository;
use MageStack\Integration\Model\Queue\Publisher as AttemptPublisher;
use MageStack\Integration\Model\SystemConfig\Source\CronSchedule;
use MageStack\Integration\Api\Data\ApiAttemptInterface;
use MageStack\Integration\Model\Service\ApiAttemptFilterService;
use MageStack\Integration\Model\Service\Integration\IntegrationBuilderFactory;
use MageStack\Integration\Model\Service\Integration\IntegrationBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;

final class Dispatcher
{
    private const DEFAULT_CRON_EXPRESSION = '*/5 * * * *';
    private const BATCH_SIZE = 100;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ApiAttemptRepository $attemptRepository,
        private readonly AdminConfig $adminConfig,
        private readonly CronSchedule $cronScheduleSource,
        private readonly DateTime $dateTime,
        private readonly AttemptPublisher $publisher,
        private readonly ApiAttemptFilterService $apiRetryFilter,
        private readonly IntegrationBuilderFactory $integrationBuilderFactory
    ) {}

    public function run(): int
    {
        // Interval is currently only used to size the log/context; eligibility
        // is driven by attempt count below, not by the created_at window,
        // so a slow-processed batch can't fall permanently out of range.
        $intervalMinutes = $this->getIntervalMinutes();
        if ($intervalMinutes === null) {
            return 0;
        }

        $currentTimestamp = $this->dateTime->gmtTimestamp();

        $now = $this->dateTime->gmtDate(
            'Y-m-d H:i:s',
            $currentTimestamp
        );

        $from = $this->dateTime->gmtDate(
            'Y-m-d H:i:s',
            $currentTimestamp - ($intervalMinutes * 60)
        );

        $items = $this->apiRetryFilter->getFiltered(
            $from,
            $now,
            self::BATCH_SIZE
        );

        $published = 0;

        foreach ($items as $item) {
            if ($item->getAttempt() > $item->getMaxAttempt()) {
                continue;
            }

            if ($item->getAttempt() === $item->getMaxAttempt()) {
                $this->markExhausted($item);
                continue;
            }

            $apiRequest = $this->buildPayload($item);
            $this->publisher->publishAttemptData($apiRequest);

            if (!$this->incrementAttempt($item)) {
                // Logged inside incrementAttempt(); skip counting this one
                // as published since we can't confirm its state was persisted.
                continue;
            }

            $published++;
        }

        return $published;
    }

    /**
     * Get retry interval in minutes from configured cron expression.
     */
    private function getIntervalMinutes(): ?int
    {
        try {
            $cronExpression = $this->adminConfig->getCronSchedule();

            return $this->cronScheduleSource->getIntervalMinutes(
                $cronExpression ?: self::DEFAULT_CRON_EXPRESSION
            );
        } catch (\Throwable $exception) {
            $this->logger->critical(
                'Unable to determine dispatcher cron interval.',
                [
                    'exception' => $exception,
                ]
            );

            return null;
        }
    }

    /**
     * Bump attempt count past max to mark the item as exhausted/no longer retryable.
     */
    private function markExhausted(ApiAttemptInterface $item): void
    {
        $item->setAttempt($item->getAttempt() + 1);

        try {
            $this->attemptRepository->save($item);
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Failed to mark attempt as exhausted.',
                [
                    'id' => $item->getId(),
                    'exception' => $exception,
                ]
            );
        }
    }

    /**
     * Increment and persist attempt count after a successful publish.
     *
     * @return bool True if the save succeeded.
     */
    private function incrementAttempt(ApiAttemptInterface $item): bool
    {
        $item->setAttempt($item->getAttempt() + 1);

        try {
            $this->attemptRepository->save($item);

            return true;
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Failed to update attempt count after publish.',
                [
                    'id' => $item->getId(),
                    'exception' => $exception,
                ]
            );

            return false;
        }
    }

    /**
     * Build queue payload.
     */
    private function buildPayload(ApiAttemptInterface $item): IntegrationBuilder
    {
        $integrationBuilder = $this->integrationBuilderFactory->create();

        $integrationBuilder->setApiCode($item->getApiCode())
            ->setEndpointCode($item->getEndpointCode())
            ->setWebsiteCode($item->getWebsiteCode())
            ->setData($item->getInputData())
            ->setUrlParams($item->getUrlParams())
            ->setAttempt($item->getAttempt());

        return $integrationBuilder;
    }
}
