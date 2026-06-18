<?php

declare(strict_types=1);

namespace MageStack\Integration\Block\Adminhtml\Credential;

use MageStack\Integration\Model\Auth\AuthenticationPool;
use MageStack\Integration\Model\IntegrationConfig;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\Data\Form as DataForm;

class Form extends Generic
{
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        private readonly IntegrationConfig $integrationConfig,
        private readonly AuthenticationPool $authenticationPool,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $data
        );
    }

    protected function _prepareForm(): self
    {
        /** @var DataForm $form */
        $form = $this->_formFactory->create([
            'data' => [
                'id'     => 'magestack_credential_form',
                'action' => $this->getUrl(
                    'integration/index/save'
                ),
                'method' => 'post'
            ]
        ]);

        $form->setUseContainer(true);

        $apis = $this->integrationConfig->resolve()['apis'] ?? [];

        if ($apis) {
            foreach ($apis as $apiCode => $apiConfig) {
                $authType = (string)(
                    $apiConfig['authentication']['type']
                    ?? 'no_auth'
                );

                $provider = $this->authenticationPool->get(
                    $authType
                );

                $fields = $provider->getConfigurationFields();

                if (!$fields) {
                    continue;
                }

                $fieldset = $form->addFieldset(
                    'fieldset_' . $apiCode,
                    [
                        'legend' => __(
                            '%1 Credentials',
                            strtoupper($apiCode)
                        )
                    ]
                );

                foreach ($fields as $field) {

                    $fieldset->addField(
                        $apiCode . '_' . $field['code'],
                        $field['type'],
                        [
                            'name' => sprintf(
                                '[%s][%s]',
                                $apiCode,
                                $field['code']
                            ),
                            'label' => __(
                                $field['label']
                            ),
                            'required' => (bool)(
                                $field['required']
                                ?? false
                            ),
                        ]
                    );
                }
            }
        }


        $this->setForm($form);

        return parent::_prepareForm();
    }
}
