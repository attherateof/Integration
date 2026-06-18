<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Auth\Pool;

class NoAuthProvider extends AbstractProvider
{
    public function getType(): string
    {
        return 'none';
    }
}