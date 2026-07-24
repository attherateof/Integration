<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Service;

use MageStack\Integration\Api\CredentialRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class CredentialProvider
{
    private const DEFAULT_WEBSITE_CODE = 'base';

    public function __construct(
        private readonly CredentialRepositoryInterface $credentialRepository,
        private readonly WebsiteRepositoryInterface $websiteRepository,
        private readonly SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
    ) {}

    private function getWebsiteId(string $websiteCode): int
    {
        return (int) $this->websiteRepository
            ->get($websiteCode)
            ->getId();
    }

    private function getDefaultWebsiteId(): int
    {
        try {
            return $this->getWebsiteId(self::DEFAULT_WEBSITE_CODE);
        } catch (NoSuchEntityException $e) {
            return 0;
        }
    }

    public function fetch(
        string $apiCode,
        string $websiteCode,
        string $authType,
        ?string $fieldName = null
    ): array {
        $websiteId = $this->getWebsiteId($websiteCode);
        $defaultWebsiteId = $this->getDefaultWebsiteId();

        $websiteIds = ($defaultWebsiteId !== 0 && $defaultWebsiteId !== $websiteId)
            ? [$websiteId, $defaultWebsiteId]
            : [$websiteId];

        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder
            ->addFilter('api_code', $apiCode)
            ->addFilter('website_id', $websiteIds, 'in')
            ->addFilter('auth_type', $authType);

        if ($fieldName !== null) {
            $searchCriteriaBuilder->addFilter('field_name', $fieldName);
        }

        $items = $this->credentialRepository
            ->getList($searchCriteriaBuilder->create())
            ->getItems();

        $specific = [];
        $fallback = [];

        foreach ($items as $item) {
            if ((int) $item->getWebsiteId() === $websiteId) {
                $specific[$item->getFieldName()] = $item->getValue();
            } else {
                $fallback[$item->getFieldName()] = $item->getValue();
            }
        }

        // Specific-website values win; default-website values fill in the gaps.
        return $specific + $fallback;
    }
}
