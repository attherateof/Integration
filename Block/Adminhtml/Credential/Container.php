<?php

declare(strict_types=1);

namespace MageStack\Integration\Block\Adminhtml\Credential;

use Magento\Backend\Block\Widget\Container as WidgetContainer;

class Container extends WidgetContainer
{
    protected function _construct(): void
    {
        parent::_construct();

        $this->buttonList->add(
            'back',
            [
                'label' => __('Back'),
                'class' => 'back',
                'onclick' => sprintf(
                    "setLocation('%s')",
                    $this->getUrl(
                        'magestack_integration/credential/index'
                    )
                )
            ],
            -1
        );

        $this->buttonList->add(
            'save',
            [
                'label' => __('Save Credentials'),
                'class' => 'save primary',
                'onclick' => 'var form = jQuery("#magestack_credential_form");
                    if (form.validation() && form.validation("isValid")) {
                        form.trigger("submit");
                    }'
            ]
        );
    }
}
