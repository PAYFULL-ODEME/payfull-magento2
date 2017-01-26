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

class DataAssignObserver implements \Magento\Framework\Event\ObserverInterface {
    /**
     * @var \Magento\Framework\ObjectManager\ObjectManager
    */
    protected $_objectManager;
    
    protected $_orderFactory;    
    protected $_checkoutSession;
    private $_historyFactory;
    
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        HistoryFactory $historyFactory,
        \Magento\Framework\ObjectManager\ObjectManager $objectManager
    ) {        
        $this->_objectManager = $objectManager;        
        $this->_orderFactory = $orderFactory;
        $this->_checkoutSession = $checkoutSession;    
        $this->_historyFactory = $historyFactory;
    }
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $orderIds = $observer->getEvent()->getOrderIds();        
        if (count($orderIds)) {
            $orderId = $orderIds[0];            
            $order = $this->_orderFactory->create()->load($orderId); 
            $payfull = $this->_checkoutSession->getPayfull();
            $commission = $payfull['payfull_commission'];
            unset($payfull['payfull_commission']);
            $order->setPayfullCommission($commission);
            $order->save();
            $payfulldata = $this->_checkoutSession->getPayfulllog();
            $historyModel = $this->_historyFactory->create();
            if(isset($payfulldata['transaction_id'])){
                $orderIncrementId = $order->getIncrementId();
                $payfulldata['order_id'] = $orderIncrementId;                
                $historyModel->setData($payfulldata);
                $historyModel->save();
            }
        }
    }
}
