<?php

declare(strict_types=1);

namespace MageStack\Integration\Cron;

use Psr\Log\LoggerInterface;
use MageStack\Integration\Model\Cron\Dispatcher;

class Process
{
    public function __construct(private readonly LoggerInterface $logger, private readonly Dispatcher $dispatcher) {}

    public function execute(): void
    {
        $this->dispatcher->run();
    }
}
