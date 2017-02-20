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
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

class SendMail implements \Magento\Framework\Event\ObserverInterface {
    /**
     * @var \Magento\Framework\ObjectManager\ObjectManager
    */
    protected $_objectManager;    
    protected $_orderFactory;    
    protected $_checkoutSession;
    protected $_historyFactory;
    protected $logger;
    protected $helper;
    protected $orderSender;
    
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        HistoryFactory $historyFactory,
        Payfullapi $helper,
        \Psr\Log\LoggerInterface $logger,
        OrderSender $orderSender,
        \Magento\Framework\ObjectManager\ObjectManager $objectManager
    ) {        
        $this->_objectManager = $objectManager;        
        $this->_orderFactory = $orderFactory;
        $this->_checkoutSession = $checkoutSession;    
        $this->_historyFactory = $historyFactory;
        $this->logger = $logger;
        $this->orderSender = $orderSender;
        $this->helper = $helper;
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

            $payfulldata = $this->_checkoutSession->getPayfulllog();
            if($payfulldata['use3d'] == 'Yes' || ($payfulldata['bank_id'] == 'BKMExpress' && $payfulldata['mail_send'] != '1') ){
                try {
                    $this->orderSender->send($order);
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                }
            }
        }
        if(isset($payfulldata)){
            unset($payfulldata);
        }
        /*if(isset($this->_checkoutSession)){
            unset($this->_checkoutSession);
        }*/
    }
}
