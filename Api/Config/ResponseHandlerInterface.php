<?php

declare(strict_types=1);

namespace MageStack\Integration\Api\Config;

interface ResponseHandlerInterface
{
    /**
     * Handle the response payload and return normalized data.
     *
     * @param array<int|string, mixed> $response
     * @return array<int|string, mixed>
     */
    public function handle(array $response): array;
}
