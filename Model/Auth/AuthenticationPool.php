<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Auth;

use MageStack\Integration\Api\Auth\AuthenticationProviderInterface;

class AuthenticationPool
{
    private array $providers = [];

    public function __construct(
        array $providers = []
    ) {
        $this->providers = $providers;
    }

    public function get(string $type): AuthenticationProviderInterface
    {
        if (!isset($this->providers[$type])) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Authentication type "%1" not found.', $type)
            );
        }

        if (!$this->providers[$type] instanceof AuthenticationProviderInterface) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Authentication type "%1" is not a valid provider.', $type)
            );
        }

        return $this->providers[$type];
    }

    public function supportedTypes(): array
    {
        return array_map('strtolower', array_keys($this->providers));
    }
}
