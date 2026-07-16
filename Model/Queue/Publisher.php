<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Queue;

use Magento\Framework\MessageQueue\PublisherInterface;

class Publisher
{
    public function __construct(private readonly PublisherInterface $publisher) {}

    public function publishAttemptData(array $payload): void
    {
        $this->publisher->publish('magestack.integration.attempt.process', $payload);
    }
}
