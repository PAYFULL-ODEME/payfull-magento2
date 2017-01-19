<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace T4u\Payfull\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use T4u\Payfull\Helper\Payfullapi;
use T4u\Payfull\Model\HistoryFactory;

/**
 * Class Cardinfo
 */
class Cardinfo extends Action
{
    private $_historyFactory;

    private $result;

    private $helper;    

    public function __construct(
        Context $context,
        JsonFactory    $resultJsonFactory,
        Payfullapi $helper,
        HistoryFactory $historyFactory
    ) {
        parent::__construct($context);
        $this->helper = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_historyFactory = $historyFactory;
    }
    /**
     * @inheritdoc
     */
    public function execute()
    {
        // echo "<pre>";
        // print_r($_POST);
        if(isset($_POST)){           
            $defaults = $_POST;           
        }
		$cc_number='';        
        if(!isset($_POST['cc_number'])){           
            return false;           
        }
        if(isset($_POST['cc_number'])){
            $cc_number = $_POST['cc_number'];
        }
        if(strlen($cc_number<16)){
            return false;
        }
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        if($cc_number){
            $this->result = $this->helper->sendSale($defaults, 'Sale');
        }
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');

        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');

        $grandTotal = $cart->getQuote()->getGrandTotal();
        $getClientIp = $this->helper->getClientIp();


        $resultj = $this->resultJsonFactory->create();

        $historyModel = $this->_historyFactory->create();

        $collection = $historyModel->getCollection();
        $order_id = 20; //temp
        $field = array();
        foreach ($collection->getData() as $data ) 
        {
            $field = array_keys($data);
            break;
        }
        //  add in if last && $this->result->status === true
        if(isset($this->result) ){
            foreach ($this->result as $key => $value) {
                foreach ($field as $keys) 
                {
                    if($key == $keys){
                        if($key == 'total'){
                            $commission_total = $value - $grandTotal;
                            $historyModel->setData('commission_total',$commission_total);
                        }
                        $historyModel->setData($key,$value);
                        break;
                    }elseif($key == 'time'){
                        $date = explode(' ', $value);
                        $historyModel->setData('date_added',$date['0']);
                    }elseif($keys == 'order_id'){
                        $historyModel->setData('order_id',$order_id);
                    }elseif($keys == 'client_ip'){
                        $historyModel->setData($keys,$getClientIp);
                    }
                }
            }
            $historyModel->save();   
        }
        return $resultj->setData($this->result);
    }
   
}
