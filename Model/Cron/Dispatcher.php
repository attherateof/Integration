<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Cron;

use DateInterval;
use DateTimeImmutable;
use MageStack\Integration\Model\AdminConfig;
use MageStack\Integration\Model\ApiAttemptRepository;
use MageStack\Integration\Model\Queue\Publisher as AttemptPublisher;
use MageStack\Integration\Model\SystemConfig\Source\CronSchedule;
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
        private readonly AttemptPublisher $publisher
    ) {}

    public function run(): int
    {
        $this->logger->info('MageStack Integration dispatcher started.');

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

        $items = $this->attemptRepository->getListByAttemptAndNextAttemptRange(
            PHP_INT_MAX,
            $from,
            $now,
            self::BATCH_SIZE
        );

        $published = 0;

        foreach ($items as $item) {
            if ($item->getAttempt() >= $item->getMaxAttempt()) {
                $item->setAttempt($item->getAttempt() + 1);
                $this->attemptRepository->save($item);
                continue;
            }

            $this->publisher->publishAttemptData($this->buildPayload($item));
            $published++;
        }

        $this->logger->info(
            sprintf(
                'MageStack Integration dispatcher published %d attempt(s).',
                $published
            )
        );

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
     * Build queue payload.
     *
     * @param object $item
     */
    private function buildPayload(object $item): array
    {
        return [
            'attempt_id' => $item->getId(),
            'api_code' => $item->getApiCode(),
            'endpoint_code' => $item->getEndpointCode(),
            'website_code' => $item->getWebsiteCode(),
            'request_body' => $item->getRequestBody(),
            'headers' => $item->getHeaders(),
            'url_params' => $item->getUrlParams(),
            'attempt' => $item->getAttempt(),
            'max_attempt' => $item->getMaxAttempt(),
            'next_attempt_at' => $item->getNextAttemptAt(),
        ];
    }
}
