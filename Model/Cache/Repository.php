<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Cache;

use Magento\Framework\Cache\FrontendInterface;
use Magento\Framework\Serialize\SerializerInterface;
use MageStack\Integration\Model\Cache\Type\Integration as IntegrationCacheType;

class Repository
{
    private const CACHE_ID_PREFIX = 'integration_cache_';
    private const DEFAULT_TTL = 60 * 60 * 24 * 365;

    private readonly FrontendInterface $cacheFrontend;

    public function __construct(
        IntegrationCacheType $cacheFrontend,
        private readonly SerializerInterface $serializer
    ) {
        $this->cacheFrontend = $cacheFrontend;
    }

    public function save(
        array $data,
        string $apiCode,
        string $websiteCode,
        ?int $ttl = null
    ): void {
        $ttl = $ttl ?? self::DEFAULT_TTL;

        $this->cacheFrontend->save(
            $this->getCacheId($apiCode, $websiteCode),
            $this->serializer->serialize($data),
            [],
            $ttl
        );
    }

    public function load(
        string $apiCode,
        string $websiteCode
    ): array|false {
        $cached = $this->cacheFrontend->load(
            $this->getCacheId($apiCode, $websiteCode)
        );

        if ($cached === false) {
            return false;
        }

        return $this->serializer->unserialize($cached);
    }

    public function remove(
        string $apiCode,
        string $websiteCode
    ): void {
        $this->cacheFrontend->remove(
            $this->getCacheId($apiCode, $websiteCode)
        );
    }

    private function getCacheId(
        string $apiCode,
        string $websiteCode
    ): string {
        return self::CACHE_ID_PREFIX
            . md5($apiCode . '|' . $websiteCode);
    }
}
