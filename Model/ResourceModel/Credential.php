<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Credential extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init(
            'magestack_integration_credential',
            'credential_id'
        );
    }
}
