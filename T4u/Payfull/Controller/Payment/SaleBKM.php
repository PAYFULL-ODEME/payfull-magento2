<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace T4u\Payfull\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use T4u\Payfull\Helper\Payfullapi;
use T4u\Payfull\Model\HistoryFactory;

/**
 * Class SaleBKM
 */
class SaleBKM extends Action
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
        // echo "ssss";exit;
        // print_r($_POST);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');

        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');

        $grandTotal = $cart->getQuote()->getGrandTotal();
        $getClientIp = $this->helper->getClientIp();
        $baseUrl = $storeManager->getStore()->getBaseUrl();
        $defaults = array(
            "bank_id"         => 'BKMExpress',
            "return_url"      => $baseUrl.'/payfull/payment/ReturnBKM.php' 
        );
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        // $this->result = $this->helper->sendSale($defaults, 'BKM');

        $defaults = array_merge($defaults, $_POST);

        $params = $this->helper->_createParmListSale('api_merch', $defaults, 'SaleInstallment');

        $this->result = $this->helper->bindCurl($params, 'pass123', 'https://dev.payfull.com/integration/api/v1');

        $this->result = json_decode($this->result);        
        
        $resultj = $this->resultJsonFactory->create();
        // return $resultj->setData($this->result);
        /***create and save**/
        $historyModel = $this->_historyFactory->create();

        $collection = $historyModel->getCollection();

        // $historyModel->setData('store_id','1');
        // $historyModel->setData('order_id','12');
        $order_id = 1; //temp
        $field = array();
        foreach ($collection->getData() as $data ) 
        {
            $field = array_keys($data);
            break;
        }
        // add in if last && $this->result->status === 1
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
