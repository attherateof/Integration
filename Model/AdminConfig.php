<?php

declare(strict_types=1);

namespace MageStack\Integration\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class AdminConfig
{
    private const XML_PATH_ENABLED = 'magestack_integration/general/enabled';
    private const XML_PATH_CRON_SCHEDULE = 'magestack_integration/cron/schedule';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {}

    public function isEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(self::XML_PATH_ENABLED);
    }

    public function getCronSchedule(): ?string
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_CRON_SCHEDULE);
        return $value === null ? null : (string) $value;
    }

    // public static function getCronConfigPath(): string
    // {
    //     return self::XML_PATH_CRON_SCHEDULE;
    // }
}
