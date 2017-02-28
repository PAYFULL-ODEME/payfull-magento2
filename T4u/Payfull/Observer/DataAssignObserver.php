<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace T4u\Payfull\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Framework\ObjectManager\ObjectManager;
use T4u\Payfull\Model\HistoryFactory;
use T4u\Payfull\Helper\Payfullapi;

class DataAssignObserver implements \Magento\Framework\Event\ObserverInterface {
    /**
     * @var \Magento\Framework\ObjectManager\ObjectManager
    */
    protected $_objectManager;
    
    protected $_orderFactory;    
    protected $_checkoutSession;
    private $_historyFactory;
    protected $logger;
    private $helper;
    
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        HistoryFactory $historyFactory,
        Payfullapi $helper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\ObjectManager\ObjectManager $objectManager
    ) {        
        $this->_objectManager = $objectManager;        
        $this->_orderFactory = $orderFactory;
        $this->_checkoutSession = $checkoutSession;    
        $this->_historyFactory = $historyFactory;
        $this->logger = $logger;
        $this->helper = $helper;
    }
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if($observer->getEvent()->getOrder()->getPayment()->getMethodInstance()->getCode() == 'payfull'){
            $order = $observer->getEvent()->getOrder(); 

            $payfull = $this->_checkoutSession->getPayfull();
            if(isset($payfull['payfull_commission'])){
                $commission = $payfull['payfull_commission'];
                unset($payfull['payfull_commission']);
                $order->setPayfullCommission($commission);
            }
            
            $payfulldata = $this->_checkoutSession->getPayfulllog();

            if($payfulldata['status'] == 'Complete'){
                $order->setStatus('complete');
            } else {
                $order->setStatus('canceled');
            }
            
            $historyModel = $this->_historyFactory->create();
           
            if(isset($payfulldata['transaction_id'])){    
                $orderIncrementId = $order->getIncrementId();
                $payfulldata['order_id'] = $orderIncrementId;  
                $historyModel->setData($payfulldata);
                $historyModel->save();
            }
            unset($payfulldata['status']);
        }
    }
}
