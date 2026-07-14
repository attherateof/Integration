<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Config\Pool;

use Magento\Framework\Exception\LocalizedException;
use MageStack\Integration\Api\Config\ResponseHandlerInterface;

class ResponseHandlerPool
{
    /**
     * @param ResponseHandlerInterface[] $handlers
     */
    public function __construct(
        private readonly array $handlers = []
    ) {}

    public function get(string $code): ResponseHandlerInterface
    {
        if (!isset($this->handlers[$code])) {
            throw new LocalizedException(
                __('Response handler "%1" is not configured.', $code)
            );
        }

        $handler = $this->handlers[$code];

        if (!$handler instanceof ResponseHandlerInterface) {
            throw new LocalizedException(
                __('Response handler "%1" must implement %2.', $code, ResponseHandlerInterface::class)
            );
        }

        return $handler;
    }
}
