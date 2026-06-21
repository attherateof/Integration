<?php

declare(strict_types=1);

namespace MageStack\Integration\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

class Actions extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct(
            $context,
            $uiComponentFactory,
            $components,
            $data
        );
    }

    public function prepareDataSource(
        array $dataSource
    ): array {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            $item[$this->getData('name')]['view'] = [
                'href' => $this->urlBuilder->getUrl(
                    'magestack_integration/credential/edit',
                    [
                        'api_code' => $item['api_code']
                    ]
                ),
                'label' => __('Edit')
            ];
        }

        return $dataSource;
    }
}
