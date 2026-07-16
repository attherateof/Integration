<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\ResourceModel\ApiAttempt;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MageStack\Integration\Model\Api\ApiAttempt as ApiAttemptModel;
use MageStack\Integration\Model\ResourceModel\ApiAttempt as ApiAttemptResource;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(ApiAttemptModel::class, ApiAttemptResource::class);
    }
}
