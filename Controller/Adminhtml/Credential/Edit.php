<?php

declare(strict_types=1);

namespace MageStack\Integration\Controller\Adminhtml\Credential;

use MageStack\Integration\Model\IntegrationConfig;
use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\View\Result\PageFactory;
use MageStack\Integration\Model\IntegrationContext;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'MageStack_Integration::integration';

    public function __construct(
        Action\Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly IntegrationConfig $integrationConfig,
        private readonly IntegrationContext $integrationContext
    ) {
        parent::__construct($context);
    }

    public function execute(): Page|Redirect
    {
        $apiCode = (string)$this->getRequest()->getParam(
            'api_code'
        );

        if ($apiCode === '') {
            $this->messageManager->addErrorMessage(
                __('API code is missing.')
            );

            return $this->resultRedirectFactory->create()
                ->setPath('*/*/index');
        }

        $config = $this->integrationConfig->resolve();

        $apiConfig = $config['apis'][$apiCode] ?? null;

        if ($apiConfig === null) {
            $this->messageManager->addErrorMessage(
                __('API "%1" does not exist.', $apiCode)
            );

            return $this->resultRedirectFactory->create()
                ->setPath('*/*/index');
        }

        $title = $apiConfig['title'] ?? $apiCode;

        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        $resultPage->setActiveMenu(
            self::ADMIN_RESOURCE
        );

        $resultPage->addBreadcrumb(
            __('API Integrations'),
            __('API Integrations')
        );

        $resultPage->addBreadcrumb(
            __($title),
            __($title)
        );

        $resultPage->getConfig()
            ->getTitle()
            ->prepend(
                __('%1', $title)
            );

        $this->integrationContext->setApiCode(
            $apiCode
        );

        $this->integrationContext->setApiConfig(
            $apiConfig
        );

        return $resultPage;
    }
}
