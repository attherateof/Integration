<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Api;

use Magento\Framework\Serialize\SerializerInterface;
use MageStack\Integration\Model\Cache\Type\Integration as IntegrationCacheType;

class ResponseCacheRepository
{
    private const CACHE_PREFIX = 'integration_api_resp_';
    private const DEFAULT_TTL = 3600;

    public function __construct(
        private readonly IntegrationCacheType $cacheFrontend,
        private readonly SerializerInterface $serializer
    ) {}

    public function get(string $key): array|false
    {
        $cached = $this->cacheFrontend->load(self::CACHE_PREFIX . $key);

        if ($cached === false) {
            return false;
        }

        return $this->serializer->unserialize($cached);
    }

    public function save(string $key, array $data, ?int $ttl = null): void
    {
        $ttl = $ttl ?? self::DEFAULT_TTL;

        $this->cacheFrontend->save(
            self::CACHE_PREFIX . $key,
            $this->serializer->serialize($data),
            [],
            $ttl
        );
    }

    public function remove(string $key): void
    {
        $this->cacheFrontend->remove(self::CACHE_PREFIX . $key);
    }
}
