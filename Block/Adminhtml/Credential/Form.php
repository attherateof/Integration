<?php

declare(strict_types=1);

namespace MageStack\Integration\Block\Adminhtml\Credential;

use MageStack\Integration\Model\Auth\AuthenticationPool;
use MageStack\Integration\Model\IntegrationConfig;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\Data\Form as DataForm;
use MageStack\Integration\Api\CredentialRepositoryInterface;
use MageStack\Integration\Model\IntegrationContext;

class Form extends Generic
{
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        private readonly CredentialRepositoryInterface $credentialRepository,
        private readonly IntegrationConfig $integrationConfig,
        private readonly AuthenticationPool $authenticationPool,
        private readonly IntegrationContext $integrationContext,
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
                    'magestack_integration/credential/save'
                ),
                'method' => 'post'
            ]
        ]);

        $form->setUseContainer(true);

        $apiCode = $this->integrationContext->getApiCode();
        $apiConfig = $this->integrationContext->getApiConfig();

        if (!$apiCode || !$apiConfig) {
            $this->setForm($form);

            return parent::_prepareForm();
        }

        $websiteId = $this->getWebsiteId();

        $savedCredentials = $this->credentialRepository
            ->getByApiCodeAndWebsite(
                $apiCode,
                $websiteId
            );

        $authType = (string)(
            $apiConfig['authentication']['type']
            ?? 'no_auth'
        );

        $provider = $this->authenticationPool->get(
            $authType
        );

        $fields = $provider->getConfigurationFields();

        $form->addField(
            'website_id',
            'hidden',
            [
                'name'  => 'website_id',
                'value' => $websiteId
            ]
        );

        $form->addField(
            'api_code',
            'hidden',
            [
                'name'  => 'api_code',
                'value' => $apiCode
            ]
        );

        $fieldset = $form->addFieldset(
            'fieldset_' . $apiCode,
            [
                'legend' => __(
                    '%1 Credentials',
                    $apiConfig['title'] ?? strtoupper($apiCode)
                )
            ]
        );

        foreach ($fields as $fieldName => $fieldConfig) {
            $fieldset->addField(
                $apiCode . '_' . $fieldName,
                $fieldConfig['type'],
                [
                    'name' => sprintf(
                        'credentials[%s][%s][%s]',
                        $apiCode,
                        $authType,
                        $fieldName
                    ),
                    'label' => __($fieldConfig['label']),
                    'class' => $fieldConfig['class'] ?? '',
                    'autocomplete' => 'off',
                    'value' => $savedCredentials[$fieldName] ?? ''
                ]
            );
        }

        $this->setForm($form);

        return parent::_prepareForm();
    }

    public function getWebsiteId(): int
    {
        return (int)$this->getRequest()->getParam('website', 0);
    }
}
