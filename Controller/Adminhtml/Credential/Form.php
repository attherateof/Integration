<?php

/**
 * Copyright © MIT All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace MageStack\Integration\Controller\Adminhtml\Credential;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

class Form implements HttpGetActionInterface
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * Constructor
     *
     * @param PageFactory $resultPageFactory
     */
    public function __construct(PageFactory $resultPageFactory)
    {
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        return $this->resultPageFactory->create();
    }
}
