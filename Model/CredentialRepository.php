<?php

declare(strict_types=1);

namespace MageStack\Integration\Model;

use MageStack\Integration\Api\CredentialRepositoryInterface;
use MageStack\Integration\Api\Data\CredentialInterface;
use MageStack\Integration\Api\Data\CredentialSearchResultsInterface;
use MageStack\Integration\Api\Data\CredentialSearchResultsInterfaceFactory;
use MageStack\Integration\Model\ResourceModel\Credential as CredentialResource;
use MageStack\Integration\Model\ResourceModel\Credential\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class CredentialRepository implements CredentialRepositoryInterface
{
    public function __construct(
        private readonly CredentialResource $resource,
        private readonly CredentialFactory $credentialFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly CredentialSearchResultsInterfaceFactory $searchResultsFactory
    ) {}

    public function save(
        CredentialInterface $credential
    ): CredentialInterface {
        try {
            $this->resource->save($credential);
        } catch (\Throwable $exception) {
            throw new CouldNotSaveException(
                __('Unable to save credential.'),
                $exception
            );
        }

        return $credential;
    }

    /**
     * @param CredentialInterface[] $credentials
     */
    public function saveMultiple(
        array $credentials
    ): void {
        try {
            $this->resource->saveMultiple(
                $credentials
            );
        } catch (\Throwable $exception) {
            throw new CouldNotSaveException(
                __('Unable to save credentials.'),
                $exception
            );
        }
    }

    public function get(
        int $credentialId
    ): CredentialInterface {
        $credential = $this->credentialFactory->create();

        $this->resource->load(
            $credential,
            $credentialId
        );

        if (!$credential->getId()) {
            throw new NoSuchEntityException(
                __('Credential with ID "%1" does not exist.', $credentialId)
            );
        }

        return $credential;
    }

    public function getList(
        SearchCriteriaInterface $searchCriteria
    ): CredentialSearchResultsInterface {
        $collection = $this->collectionFactory->create();

        $this->collectionProcessor->process(
            $searchCriteria,
            $collection
        );

        $searchResults = $this->searchResultsFactory->create();

        $searchResults->setSearchCriteria(
            $searchCriteria
        );

        $searchResults->setItems(
            $collection->getItems()
        );

        $searchResults->setTotalCount(
            (int)$collection->getSize()
        );

        return $searchResults;
    }

    public function delete(
        CredentialInterface $credential
    ): bool {
        try {
            $this->resource->delete($credential);
        } catch (\Throwable $exception) {
            throw new CouldNotDeleteException(
                __('Unable to delete credential.'),
                $exception
            );
        }

        return true;
    }

    public function deleteById(
        int $credentialId
    ): bool {
        return $this->delete(
            $this->getById($credentialId)
        );
    }

    public function getByApiCodeAndWebsite(
        string $apiCode,
        int $websiteId
    ): array {
        $collection = $this->collectionFactory->create();

        $collection->addFieldToFilter(
            CredentialInterface::API_CODE,
            $apiCode
        );

        $collection->addFieldToFilter(
            CredentialInterface::WEBSITE_ID,
            $websiteId
        );

        $credentials = [];

        foreach ($collection as $credential) {
            $credentials[$credential->getFieldName()] =
                $credential->getValue();
        }

        return $credentials;
    }

    public function getByWebsiteId(
        int $websiteId
    ): array {
        $collection = $this->collectionFactory->create();

        $collection->addFieldToFilter(
            CredentialInterface::WEBSITE_ID,
            $websiteId
        );

        return $collection->getItems();
    }

    public function getCredential(
        string $apiCode,
        int $websiteId,
        string $fieldName
    ): ?CredentialInterface {
        $collection = $this->collectionFactory->create();

        $collection->addFieldToFilter(
            CredentialInterface::API_CODE,
            $apiCode
        );

        $collection->addFieldToFilter(
            CredentialInterface::WEBSITE_ID,
            $websiteId
        );

        $collection->addFieldToFilter(
            CredentialInterface::FIELD_NAME,
            $fieldName
        );

        $credential = $collection->getFirstItem();

        return $credential->getId()
            ? $credential
            : null;
    }

    public function saveCredential(
        string $apiCode,
        int $websiteId,
        string $authType,
        string $fieldName,
        string $value
    ): void {
        $credential = $this->getCredential(
            $apiCode,
            $websiteId,
            $fieldName
        );

        if ($credential === null) {
            $credential = $this->credentialFactory->create();
        }

        $credential->setApiCode($apiCode);
        $credential->setWebsiteId($websiteId);
        $credential->setAuthType($authType);
        $credential->setFieldName($fieldName);
        $credential->setValue($value);

        $this->save($credential);
    }

    public function deleteByApiCodeAndWebsite(
        string $apiCode,
        int $websiteId
    ): void {
        $collection = $this->collectionFactory->create();

        $collection->addFieldToFilter(
            CredentialInterface::API_CODE,
            $apiCode
        );

        $collection->addFieldToFilter(
            CredentialInterface::WEBSITE_ID,
            $websiteId
        );

        foreach ($collection as $credential) {
            $this->delete($credential);
        }
    }
}
