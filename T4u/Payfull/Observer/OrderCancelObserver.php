<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace T4u\Payfull\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Framework\ObjectManager\ObjectManager;
use T4u\Payfull\Helper\Payfullapi;

class OrderCancelObserver implements \Magento\Framework\Event\ObserverInterface {
    /**
     * @var \Magento\Framework\ObjectManager\ObjectManager
    */
    protected $_objectManager;

    private $helper;
    private $_scopeConfig;
    
    protected $_orderFactory;    
    protected $_checkoutSession;
    protected $logger;
    protected $mymodulemodelFactory;
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\ObjectManager\ObjectManager $objectManager,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \T4u\Payfull\Model\HistoryFactory $mymodulemodelFactory,
        Payfullapi $helper
    ) {        
        $this->_objectManager = $objectManager;        
        $this->_orderFactory = $orderFactory;
        $this->_checkoutSession = $checkoutSession; 
        $this->logger = $logger;
        $this->_scopeConfig = $scopeConfig;           
        $this->helper = $helper;
        $this->mymodulemodelFactory = $mymodulemodelFactory;
    }
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getPayment()->getOrder();
        $orderId =  $order->getIncrementId();

        if ($orderId) {      
            $collection = $this->mymodulemodelFactory->create()->getCollection()
                               ->addFieldToFilter('order_id',$orderId);
            $collection = $collection->getColumnValues('transaction_id');
            $transaction_id = $collection[0];
            if($transaction_id){
                $defaults = array("type"         => 'Cancel',
                                  "transaction_id"  => $transaction_id);
                $response = $this->helper->orderCancelRefund($defaults);
                /*var_dump($response);exit;*/
            }
        }
    }
}
