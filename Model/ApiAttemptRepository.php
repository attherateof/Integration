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
use MageStack\Integration\Model\ResourceModel\ApiAttempt\Collection;

class ApiAttemptRepository
{
    public function __construct(
        private readonly ApiAttemptResource $resource,
        private readonly ApiAttemptFactory $factory,
        private readonly LoggerInterface $logger,
        private readonly ObjectManagerInterface $objectManager
    ) {}

    public function save(ApiAttemptInterface $attempt): ApiAttemptInterface
    {
        try {
            if ($attempt instanceof ApiAttempt) {
                $model = $attempt;
            } else {
                $model = $this->factory->create();
                $model->setData([
                    ApiAttemptInterface::API_CODE => $attempt->getApiCode(),
                    ApiAttemptInterface::ENDPOINT_CODE => $attempt->getEndpointCode(),
                    ApiAttemptInterface::WEBSITE_CODE => $attempt->getWebsiteCode(),
                    ApiAttemptInterface::PAYLOAD => $attempt->getPayload(),
                    ApiAttemptInterface::URL_PARAMS => $attempt->getUrlParams(),
                    ApiAttemptInterface::ATTEMPT => $attempt->getAttempt(),
                    ApiAttemptInterface::MAX_ATTEMPT => $attempt->getMaxAttempt(),
                    ApiAttemptInterface::NEXT_ATTEMPT_AT => $attempt->getNextAttemptAt(),
                ]);
            }

            $this->resource->save($model);

            return $model;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to save ApiAttempt: ' . $e->getMessage());
            throw new CouldNotSaveException(__($e->getMessage()));
        }
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
        try {
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
        } catch (\Throwable $e) {
            $this->logger->error('Failed to delete ApiAttempt: ' . $e->getMessage());
            throw new CouldNotDeleteException(__($e->getMessage()));
        }
    }

    public function deleteById(int $id): bool
    {
        $attempt = $this->getById($id);
        return $this->delete($attempt);
    }

    /**
     * Get a batch of ApiAttempt models where attempt <= maxAttempt and next_attempt_at between from and to.
     * Returns at most $limit items.
     *
     * @return ApiAttempt[]
     */
    public function getListByAttemptAndNextAttemptRange(int $maxAttempt, string $from, string $to, int $limit = 100): array
    {
        /** @var Collection $collection */
        $collection = $this->objectManager->create(Collection::class);

        $collection->addFieldToFilter(ApiAttemptInterface::ATTEMPT, ['lte' => $maxAttempt]);
        $collection->addFieldToFilter(ApiAttemptInterface::NEXT_ATTEMPT_AT, ['from' => $from, 'to' => $to]);

        $collection->setPageSize($limit);
        $collection->setCurPage(1);

        return $collection->getItems();
    }
}
