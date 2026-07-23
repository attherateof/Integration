<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Service\Integration;

use MageStack\Integration\Model\Service\ResolvedConfigProvider;
use MageStack\Integration\Model\Service\Integration\IntegrationBuilder;
use MageStack\Integration\Model\Auth\AuthenticationPool;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class AuthHeaderProvider
{
    public function __construct(
        private readonly ResolvedConfigProvider $resolvedConfigProvider,
        private readonly AuthenticationPool $authenticationPool,
        private readonly LoggerInterface $logger
    ) {}

    public function get(IntegrationBuilder $integrationRequest, array $headers): array
    {

        $apiCode = $integrationRequest->getApiCode();
        $websiteCode = $integrationRequest->getWebsiteCode();

        $authType = $this->resolvedConfigProvider->getAuthenticationType($apiCode, $websiteCode);

        if (!$authType) {
            $this->logger->warning(sprintf('No authentication type found for API "%s" and website "%s".', $apiCode, $websiteCode));
            throw new LocalizedException(__('No authentication type found for API "%1" and website "%2".', $apiCode, $websiteCode));
        }

        $provider = $this->authenticationPool->get($authType);

        $authHeaders =  $provider->buildHeaders($integrationRequest);

        return array_merge(
            $headers,
            $authHeaders
        );
    }
}
