<?php

declare(strict_types=1);

namespace MageStack\Integration\Plugin\Admin\Menu;

use MageStack\Integration\Model\AdminConfig;
use Magento\Backend\Model\Menu;
use Magento\Backend\Model\Menu\Builder;

class HideIfDisabled
{
    public function __construct(private readonly AdminConfig $adminConfig) {}

    public function afterGetResult(Builder $subject, Menu $menu): Menu
    {
        if (!$this->adminConfig->isEnabled()) {
            // remove menu item if module disabled
            try {
                $menu->remove('MageStack_Integration::index_edit');
            } catch (\Throwable $e) {
                // no-op
            }
        }

        return $menu;
    }
}
