<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Service;

use MageStack\Integration\Model\ApiAttemptRepository;
use MageStack\Integration\Api\Data\ApiAttemptInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\LocalizedException;

/**
 * Filters ApiAttempt records by a created_at time range, with an optional
 * result limit and most-recent-first ordering.
 */
class ApiAttemptFilterService
{
    private const FIELD_CREATED_AT = 'created_at';

    public function __construct(
        private readonly ApiAttemptRepository $apiAttemptRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly SortOrderBuilder $sortOrderBuilder
    ) {}

    /**
     * @param string $from Inclusive lower bound, e.g. '2026-07-01 00:00:00'
     * @param string $to Inclusive upper bound, e.g. '2026-07-23 23:59:59'
     * @param int $limit Maximum number of records to return
     * @return ApiAttemptInterface[]
     * @throws LocalizedException
     */
    public function getFiltered(string $from, string $to, int $limit = 100): array
    {
        $this->validateRange($from, $to);

        $this->searchCriteriaBuilder->addFilter(self::FIELD_CREATED_AT, $from, 'gteq');
        $this->searchCriteriaBuilder->addFilter(self::FIELD_CREATED_AT, $to, 'lteq');

        $sortOrder = $this->sortOrderBuilder
            ->setField(self::FIELD_CREATED_AT)
            ->setDirection(SortOrder::SORT_DESC)
            ->create();

        $this->searchCriteriaBuilder->setSortOrders([$sortOrder]);
        $this->searchCriteriaBuilder->setPageSize($limit);
        $this->searchCriteriaBuilder->setCurrentPage(1);

        $searchCriteria = $this->searchCriteriaBuilder->create();

        return $this->apiAttemptRepository->getList($searchCriteria)->getItems();
    }

    /**
     * @throws LocalizedException
     */
    private function validateRange(string $from, string $to): void
    {
        $fromTimestamp = strtotime($from);
        $toTimestamp = strtotime($to);

        if ($fromTimestamp === false || $toTimestamp === false) {
            throw new LocalizedException(__('Invalid date format supplied for from/to range.'));
        }

        if ($fromTimestamp > $toTimestamp) {
            throw new LocalizedException(__('The "from" date cannot be after the "to" date.'));
        }
    }
}
