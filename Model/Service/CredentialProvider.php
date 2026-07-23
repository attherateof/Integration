<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Service;

use MageStack\Integration\Api\CredentialRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class CredentialProvider
{
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

    public function fetch(
        string $apiCode,
        string $websiteCode,
        string $authType,
        ?string $fieldName = null
    ): array {
        $credentials = [];
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $websiteId = $this->getWebsiteId($websiteCode);
        $searchCriteriaBuilder
            ->addFilter('api_code', $apiCode)
            ->addFilter('website_code', $websiteId)
            ->addFilter('auth_type', $authType);

        if ($fieldName !== null) {
            $searchCriteriaBuilder->addFilter('field_name', $fieldName);
        }

        $searchCriteria = $searchCriteriaBuilder->create();
        $items = $this->credentialRepository->getList($searchCriteria)->getItems();
        foreach ($items as $item) {
            $credentials[$item->getFieldName()] = $item->getValue();
        }

        return $credentials;
    }
}
