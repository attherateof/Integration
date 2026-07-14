<?php

declare(strict_types=1);

namespace MageStack\Integration\Api\Config;

interface ResponseValidatorInterface
{
    /**
     * Validate the response payload and return whether it is valid.
     *
     * @param array<int|string, mixed> $response
     * @return bool
     */
    public function validate(array $response): bool;
}
