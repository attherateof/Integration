<?php

declare(strict_types=1);

namespace MageStack\Integration\Api\Config;

interface RequestBuilderInterface
{
    /**
     * Build a request payload/config for a given website and context.
     *
     * @param int $websiteId
     * @param array<int|string, int|string|float|bool|array|object> $context
     * @return array<int|string, mixed>
     */
    public function build(int $websiteId, array $context): array;
}
