<?php

declare(strict_types=1);

namespace MageStack\Integration\Controller\Adminhtml\Credential;

use MageStack\Integration\Api\CredentialRepositoryInterface;
use MageStack\Integration\Api\Data\CredentialInterface;
use MageStack\Integration\Model\Auth\AuthenticationPool;
use MageStack\Integration\Model\CredentialFactory;
use Magento\Backend\App\Action;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Throwable;

class Save extends Action
{
    public const ADMIN_RESOURCE =
    'MageStack_Integration::credentials';

    public function __construct(
        Action\Context $context,
        private readonly FormKeyValidator $formKeyValidator,
        private readonly CredentialRepositoryInterface $credentialRepository,
        private readonly CredentialFactory $credentialFactory,
        private readonly AuthenticationPool $authenticationPool,
        private readonly DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(
            ResultFactory::TYPE_REDIRECT
        );

        if (
            !$this->formKeyValidator->validate(
                $this->getRequest()
            )
        ) {
            $this->messageManager->addErrorMessage(
                __('Invalid form key.')
            );

            return $resultRedirect->setPath(
                '*/*/index'
            );
        }

        $websiteId = (int)$this->getRequest()->getParam(
            'website_id',
            0
        );

        $credentials = (array)$this->getRequest()->getParam(
            'credentials',
            []
        );

        try {
            $credentialsToSave = [];

            foreach ($credentials as $apiCode => $authTypes) {
                foreach ($authTypes as $authTypeCode => $fields) {
                    if (!is_array($fields)) {
                        continue;
                    }

                    $authType = $this->authenticationPool
                        ->get($authTypeCode)
                        ->getType();

                    foreach ($fields as $fieldName => $value) {
                        /** @var CredentialInterface $credential */
                        $credential = $this->credentialFactory->create();

                        $credential->setApiCode(
                            (string)$apiCode
                        );

                        $credential->setWebsiteId(
                            $websiteId
                        );

                        $credential->setAuthType(
                            $authType
                        );

                        $credential->setFieldName(
                            (string)$fieldName
                        );

                        $credential->setValue(
                            (string)$value
                        );

                        $credentialsToSave[] = $credential;
                    }
                }
            }

            if ($credentialsToSave) {
                $this->credentialRepository->saveMultiple(
                    $credentialsToSave
                );
            }

            $this->dataPersistor->clear(
                'magestack_credentials'
            );

            $this->messageManager->addSuccessMessage(
                __('Credentials have been saved.')
            );
        } catch (Throwable $exception) {
            $this->dataPersistor->set(
                'magestack_credentials',
                $this->getRequest()->getPostValue()
            );

            $this->messageManager->addExceptionMessage(
                $exception,
                __('Unable to save credentials.')
            );
        }

        return $resultRedirect->setPath(
            '*/*/index',
            [
                'website_id' => $websiteId
            ]
        );
    }
}
