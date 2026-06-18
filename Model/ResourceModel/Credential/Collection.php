<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\ResourceModel\Credential;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MageStack\Integration\Model\Credential;
use MageStack\Integration\Model\ResourceModel\Credential as ResourceModel;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'credential_id';

    protected function _construct(): void
    {
        $this->_init(
            Credential::class,
            ResourceModel::class
        );
    }
}
