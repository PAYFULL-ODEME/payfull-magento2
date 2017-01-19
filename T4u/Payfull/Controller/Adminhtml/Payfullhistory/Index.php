<?php
namespace T4u\Payfull\Controller\Adminhtml\Payfullhistory;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Index action
     *
     * @return void
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('T4u_Payfull::history');
        $resultPage->addBreadcrumb(__('Payfull'), __('Payfull'));
        $resultPage->addBreadcrumb(__('Payfull History'), __('Payfull History'));
        $resultPage->getConfig()->getTitle()->prepend(__('Payfull History'));

        return $resultPage;
    }
}
