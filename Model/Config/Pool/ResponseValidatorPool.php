<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Config\Pool;

use Magento\Framework\Exception\LocalizedException;
use MageStack\Integration\Api\Config\ResponseValidatorInterface;

class ResponseValidatorPool
{
    /**
     * @param ResponseValidatorInterface[] $validators
     */
    public function __construct(
        private readonly array $validators = []
    ) {}

    public function get(string $code): ResponseValidatorInterface
    {
        if (!isset($this->validators[$code])) {
            throw new LocalizedException(
                __('Response validator "%1" is not configured.', $code)
            );
        }

        $validator = $this->validators[$code];

        if (!$validator instanceof ResponseValidatorInterface) {
            throw new LocalizedException(
                __('Response validator "%1" must implement %2.', $code, ResponseValidatorInterface::class)
            );
        }

        return $validator;
    }
}
