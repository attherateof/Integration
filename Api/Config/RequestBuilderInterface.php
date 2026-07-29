<?php

declare(strict_types=1);

namespace MageStack\Integration\Api\Config;

use MageStack\Integration\Model\Service\Integration\IntegrationBuilder; // will be converted to interface

interface RequestBuilderInterface
{
    /**
     * Build the request payload and URL parameters from configuration.
     *
     * @param IntegrationBuilder $request
     * 
     * @return array{payload: array<int|string, mixed>, urlParams: array<int|string, mixed>}
     */
    public function build(IntegrationBuilder $request): array;
}
