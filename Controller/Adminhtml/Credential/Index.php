<?php

declare(strict_types=1);

namespace MageStack\Integration\Controller\Adminhtml\Credential;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'MageStack_Integration::integration';

    public function __construct(
        Action\Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): Page
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        $resultPage->setActiveMenu(
            self::ADMIN_RESOURCE
        );

        $resultPage->addBreadcrumb(
            __('MageStack'),
            __('MageStack')
        );

        $resultPage->addBreadcrumb(
            __('API Integrations'),
            __('API Integrations')
        );

        $resultPage->getConfig()
            ->getTitle()
            ->prepend(__('API Integrations'));

        return $resultPage;
    }
}
