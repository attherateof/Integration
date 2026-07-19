<?php

declare(strict_types=1);

namespace MageStack\Integration\Model;

use MageStack\Integration\Api\Data\ApiAttemptInterface;
use MageStack\Integration\Model\ResourceModel\ApiAttempt as ApiAttemptResource;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use MageStack\Integration\Model\ResourceModel\ApiAttempt\Collection;

class ApiAttemptRepository
{
    public function __construct(
        private readonly ApiAttemptResource $resource,
        private readonly ApiAttemptFactory $factory,
        private readonly ObjectManagerInterface $objectManager,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly SearchResultsInterfaceFactory $searchResultsFactory
    ) {}

    public function save(ApiAttemptInterface $attempt): ApiAttemptInterface
    {
        if (!$attempt instanceof ApiAttempt) {
            throw new \InvalidArgumentException('Invalid attempt object provided.');
        }

        $this->resource->save($attempt);

        return $attempt;
    }

    public function getById(int $id): ApiAttemptInterface
    {
        /** @var ApiAttempt $model */
        $model = $this->factory->create();
        $this->resource->load($model, $id);

        if (!$model->getId()) {
            throw new NoSuchEntityException(__('API attempt with id %1 does not exist.', $id));
        }

        return $model;
    }

    public function delete(ApiAttemptInterface $attempt): bool
    {
        if ($attempt instanceof ApiAttempt) {
            $model = $attempt;
        } else {
            $model = $this->factory->create();
            $id = $attempt->getId();
            if ($id !== null) {
                $this->resource->load($model, $id);
            }
        }

        if (!$model->getId()) {
            throw new NoSuchEntityException(__('API attempt does not exist.'));
        }

        $this->resource->delete($model);

        return true;
    }

    public function deleteById(int $id): bool
    {
        $attempt = $this->getById($id);
        return $this->delete($attempt);
    }

    /**
     * Retrieve a list of ApiAttempt entities matching the given search criteria.
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        /** @var Collection $collection */
        $collection = $this->objectManager->create(Collection::class);

        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var SearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }
}
