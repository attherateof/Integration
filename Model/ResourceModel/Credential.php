<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use MageStack\Integration\Api\Data\CredentialInterface;

class Credential extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init(
            'magestack_integration_credential',
            'credential_id'
        );
    }

    /**
     * @param CredentialInterface[] $credentials
     */
    public function saveMultiple(
        array $credentials
    ): void {
        if (empty($credentials)) {
            return;
        }

        $rows = [];

        foreach ($credentials as $credential) {
            if (!$credential instanceof CredentialInterface) {
                throw new \InvalidArgumentException(
                    'All items must implement CredentialInterface.'
                );
            }

            $rows[] = [
                CredentialInterface::API_CODE => $credential->getApiCode(),
                CredentialInterface::WEBSITE_ID => $credential->getWebsiteId(),
                CredentialInterface::AUTH_TYPE => $credential->getAuthType(),
                CredentialInterface::FIELD_NAME => $credential->getFieldName(),
                CredentialInterface::VALUE => $credential->getValue()
            ];
        }

        $this->getConnection()->insertOnDuplicate(
            $this->getMainTable(),
            $rows,
            [
                CredentialInterface::AUTH_TYPE,
                CredentialInterface::VALUE,
            ]
        );
    }
}
