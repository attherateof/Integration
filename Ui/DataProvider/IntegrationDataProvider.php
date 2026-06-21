<?php

declare(strict_types=1);

namespace MageStack\Integration\Ui\DataProvider;

use MageStack\Integration\Model\IntegrationConfig;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;

class IntegrationDataProvider extends DataProvider
{
    public function __construct(
        private readonly IntegrationConfig $integrationConfig,
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $criteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $criteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
    }

    public function getData(): array
    {
        $params = $this->request->getParams();

        $items = [];

        foreach ($this->integrationConfig->resolve()['apis'] as $apiCode => $apiConfig) {
            $items[] = [
                'id'             => $apiCode,
                'api_code'       => $apiCode,
                'title'          => $apiConfig['title'] ?? '',
                'base_url'       => $apiConfig['base_url'] ?? '',
                'auth_type'      => $apiConfig['authentication']['type'] ?? '',
                'endpoint_count' => count($apiConfig['endpoints'] ?? []),
                'website_count'  => count($apiConfig['websites'] ?? []),
                'enabled'        => !empty($apiConfig['enabled'])
            ];
        }

        /*
         * Fake search
         */
        if (!empty($params['search'])) {
            $search = strtolower((string)$params['search']);

            $items = array_filter(
                $items,
                static fn(array $item): bool =>
                str_contains(
                    strtolower(
                        implode(' ', $item)
                    ),
                    $search
                )
            );
        }

        /*
         * Fake sorting
         */
        if (!empty($params['sorting']['field'])) {
            $field = (string)$params['sorting']['field'];
            $direction = strtolower(
                (string)($params['sorting']['direction'] ?? 'asc')
            );

            usort(
                $items,
                static function (array $a, array $b) use (
                    $field,
                    $direction
                ): int {
                    $result = $a[$field] <=> $b[$field];

                    return $direction === 'desc'
                        ? -$result
                        : $result;
                }
            );
        }

        /*
         * Fake pagination
         */
        $pageSize = (int)(
            $params['paging']['pageSize']
            ?? 20
        );

        $currentPage = (int)(
            $params['paging']['current']
            ?? 1
        );

        $totalRecords = count($items);

        $items = array_slice(
            $items,
            ($currentPage - 1) * $pageSize,
            $pageSize
        );

        return [
            'totalRecords' => $totalRecords,
            'items' => array_values($items)
        ];
    }
}
