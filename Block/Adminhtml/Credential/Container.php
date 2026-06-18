<?php

namespace MageStack\Integration\Block\Adminhtml\Credential;

use Magento\Backend\Block\Widget\Container as WidgetContainer;

class Container extends WidgetContainer
{
    protected function _construct(): void
    {
        parent::_construct();

        $this->buttonList->add(
            'save',
            [
                'label' => __('Save Credentials'),
                'class' => 'save primary',
                'onclick' => 'edit_form.submit();'
            ]
        );
    }
}
