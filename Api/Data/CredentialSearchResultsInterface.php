<?php

declare(strict_types=1);

namespace MageStack\Integration\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\AbstractExtensibleObject;

interface CredentialSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get items
     *
     * @return AbstractExtensibleObject[]|CredentialInterface[]
     */
    public function getItems();

    /**
     * Set items
     *
     * @param AbstractExtensibleObject[]|CredentialInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
