<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Api\Retry;

use Magento\Framework\Stdlib\DateTime\DateTime as MagentoDateTime;
use MageStack\Integration\Model\AdminConfig;
use MageStack\Integration\Model\SystemConfig\Source\CronSchedule;

/**
 * @inheritDoc
 *
 * $attemptCount is 0-indexed: 0 = first attempt (first failure), 1 = second attempt, etc.
 * capped at maxDelayMinutes when provided.
 */
class ScheduleCalculator
{
    /**
     * @param MagentoDateTime $coreDateTime
     */
    public function __construct(
        private readonly MagentoDateTime $coreDateTime,
        private readonly AdminConfig $adminConfig,
        private readonly CronSchedule $cronSchedule
    ) {}

    /**
     * @inheritDoc
     */
    public function getNextRetryTime(
        int $attemptCount,
        int $backoffMultiplier,
        int $maxDelayMinutes = 1440
    ): \DateTimeImmutable {
        $cronExpression = $this->adminConfig->getCronSchedule();
        $initialDelayMinutes = $this->cronSchedule->getIntervalMinutes($cronExpression);

        $delayMinutes = $initialDelayMinutes * ($backoffMultiplier ** $attemptCount);
        $delayMinutes = min($delayMinutes, $maxDelayMinutes);

        $currentTimestamp = $this->coreDateTime->gmtTimestamp();
        $now = \DateTimeImmutable::createFromFormat('U', (string) $currentTimestamp);

        if ($now === false) {
            throw new \RuntimeException('Unable to parse current timestamp for retry schedule calculation.');
        }

        return $now->modify(sprintf('+%d minutes', (int) round($delayMinutes)));
    }

    /**
     * @inheritDoc
     */
    public function hasAttemptsRemaining(int $attemptCount, int $maxAttempts, bool $isEnabled): bool
    {
        if (!$isEnabled) {
            return false;
        }

        return $attemptCount < $maxAttempts;
    }
}
