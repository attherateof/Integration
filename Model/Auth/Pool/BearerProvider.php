<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Auth\Pool;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class BearerProvider extends AbstractProvider
{
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {}

    public function getType(): string
    {
        return 'bearer';
    }

    public function buildHeaders(
        array $apiConfig,
        int $websiteId
    ): array {

        $apiId = $apiConfig['@id'] ?? '';

        $token = $this->scopeConfig->getValue(
            sprintf(
                'integration_framework/%s/credentials/token',
                $apiId
            ),
            ScopeInterface::SCOPE_WEBSITES,
            $websiteId
        );

        if (!$token) {
            return [];
        }

        return [
            'Authorization' => 'Bearer ' . $token
        ];
    }

    public function getConfigurationFields(): array
    {
        return [
            [
                'code' => 'token',
                'label' => 'Bearer Token',
                'type' => 'password',
                'encrypted' => true,
                'required' => true
            ]
        ];
    }
}
