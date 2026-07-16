<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Setup;

use MageStack\Integration\Model\IntegrationConfig;
use MageStack\Integration\Model\ConfigResolver;
use MageStack\Integration\Model\Cache\Repository as CacheRepository;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteCollectionFactory;
use Psr\Log\LoggerInterface;

class IntegrationCacheRefresher
{
    public function __construct(
        private readonly IntegrationConfig $integrationConfig,
        private readonly ConfigResolver $configResolver,
        private readonly CacheRepository $cacheRepository,
        private readonly WebsiteCollectionFactory $websiteCollectionFactory,
        private readonly LoggerInterface $logger
    ) {}

    public function refreshAll(): void
    {
        try {
            $config = $this->integrationConfig->resolve();
            $apis = $config['apis'] ?? [];

            $collection = $this->websiteCollectionFactory->create();

            /** @var \Magento\Store\Model\Website $website */
            foreach ($collection as $website) {
                $websiteCode = (string) $website->getCode();

                foreach ($apis as $apiCode => $_api) {
                    // Ensure cache is removed and regenerated
                    try {
                        $this->cacheRepository->remove($apiCode, $websiteCode);
                        $this->configResolver->resolve($apiCode, $websiteCode);
                    } catch (\Throwable $e) {
                        $this->logger->warning(
                            sprintf('Failed to refresh integration cache for api=%s website=%s: %s', $apiCode, $websiteCode, $e->getMessage())
                        );
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error('Integration cache refresher failed: ' . $e->getMessage());
        }
    }
}
