<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\ResourceModel\ApiAttempt;

use Magento\Framework\ObjectManagerInterface;

class CollectionFactory
{
    public function __construct(private readonly ObjectManagerInterface $objectManager) {}

    public function create(): Collection
    {
        return $this->objectManager->create(Collection::class);
    }
}
