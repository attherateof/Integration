<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Queue;

use Magento\Framework\MessageQueue\PublisherInterface;
use MageStack\Integration\Model\Service\Integration\IntegrationBuilder;

class Publisher
{
    public function __construct(private readonly PublisherInterface $publisher) {}

    public function publishAttemptData(IntegrationBuilder $payload): void
    {
        $this->publisher->publish('magestack.integration.retry', $payload);
    }
}
