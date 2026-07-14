<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Cache\Type;

use Magento\Framework\App\Cache\Frontend\Pool as FrontendPools;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;

class Integration extends TagScope
{
    public const TYPE_IDENTIFIER = 'integration_cache';

    public const CACHE_TAG = 'INTEGRATION_CACHE';

    public function __construct(FrontendPools $cacheFrontendPool)
    {
        parent::__construct(
            $cacheFrontendPool->get(self::TYPE_IDENTIFIER),
            self::CACHE_TAG
        );
    }
}
