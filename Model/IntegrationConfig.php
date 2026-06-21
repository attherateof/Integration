<?php

declare(strict_types=1);

namespace MageStack\Integration\Model;

use Magento\Framework\Config\ReaderInterface;

class IntegrationConfig
{
    public function __construct(
        private readonly ReaderInterface $reader
    ) {}

    /**
     * ENTRY POINT (handles all CLI cases)
     */
    public function resolve(): array
    {
        return $this->reader->read();
    }
}
