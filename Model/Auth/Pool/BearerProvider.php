<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Auth\Pool;

use MageStack\Integration\Model\Service\CredentialProvider;
use MageStack\Integration\Model\Service\Integration\IntegrationBuilder;
use MageStack\Integration\Model\Service\ResolvedConfigProvider;
use Magento\Framework\Exception\LocalizedException;

class BearerProvider extends AbstractProvider
{
    public function __construct(
        private readonly CredentialProvider $credentialProvider,
        private readonly ResolvedConfigProvider $resolvedConfigProvider
    ) {}

    public function getType(): string
    {
        return 'bearer';
    }

    public function buildHeaders(
        IntegrationBuilder $integrationRequest
    ): array {
        $authType = $this->resolvedConfigProvider->getAuthenticationType(
            $integrationRequest->getApiCode(),
            $integrationRequest->getWebsiteCode()
        );

        $token = $this->credentialProvider->fetch(
            $integrationRequest->getApiCode(),
            $integrationRequest->getWebsiteCode(),
            $authType,
            'bearer_token'
        );

        if (empty($token) || isset($token['bearer_token']) || empty($token['bearer_token'])) {
            throw new LocalizedException(
                __(
                    "Bearer token must be set for api code : %s and website code :%s .",
                    $integrationRequest->getApiCode(),
                    $integrationRequest->getWebsiteCode()
                )
            );
        }

        return [
            'Authorization' => 'Bearer ' . $token['bearer_token']
        ];
    }

    /**
     * for admin config
     */
    public function getConfigurationFields(): array
    {
        return [
            'bearer_token' => [
                'label' => 'Bearer Token',
                'type' => 'password',
                'encrypted' => true,
                'class' => 'required-entry'
            ]
        ];
    }
}
