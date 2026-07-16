<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\SystemConfig\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Provides a dropdown of preset CRON schedule options for the integration cron job.
 */
class CronSchedule implements OptionSourceInterface
{
    /**
     * @var array<string, array{label: string, interval: int}>
     */
    private const OPTIONS = [
        '*/5 * * * *' => ['label' => 'Every 5 minutes', 'interval' => 5],
        '*/10 * * * *' => ['label' => 'Every 10 minutes', 'interval' => 10],
        '*/15 * * * *' => ['label' => 'Every 15 minutes', 'interval' => 15],
        '*/30 * * * *' => ['label' => 'Every 30 minutes', 'interval' => 30],
        '0 * * * *' => ['label' => 'Every 1 hour', 'interval' => 60],
        '0 */2 * * *' => ['label' => 'Every 2 hours', 'interval' => 120],
    ];

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        $options = [];

        foreach (self::OPTIONS as $value => $config) {
            $options[] = ['value' => $value, 'label' => __($config['label'])];
        }

        return $options;
    }

    /**
     * Resolve the interval in minutes for a given configured CRON expression.
     *
     * @param string $cronExpression
     * @return int
     * @throws \InvalidArgumentException
     */
    public function getIntervalMinutes(string $cronExpression): int
    {
        if (!isset(self::OPTIONS[$cronExpression])) {
            throw new \InvalidArgumentException(
                sprintf('Unknown or unsupported cron expression "%s"', $cronExpression)
            );
        }

        return self::OPTIONS[$cronExpression]['interval'];
    }
}
