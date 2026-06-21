<?php

declare(strict_types=1);

namespace MageStack\Integration\Model;

use Magento\Framework\Exception\LocalizedException;
use MageStack\Integration\Api\Config\RequestBuilderInterface;

class RequestBuilderPool
{
    /**
     * @param RequestBuilderInterface[] $builders
     */
    public function __construct(
        private readonly array $builders = []
    ) {}

    public function get(string $code): RequestBuilderInterface
    {
        if (!isset($this->builders[$code])) {
            throw new LocalizedException(
                __('Request builder "%1" is not configured.', $code)
            );
        }

        $builder = $this->builders[$code];

        if (!$builder instanceof RequestBuilderInterface) {
            throw new LocalizedException(
                __('Request builder "%1" must implement %2.', $code, RequestBuilderInterface::class)
            );
        }

        return $builder;
    }
}
