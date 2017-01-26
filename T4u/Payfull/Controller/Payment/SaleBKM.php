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

    private $_scopeConfig;

    private $_crypt;  

    protected $checkoutSession; 

    public function __construct(
        Context $context,
        JsonFactory    $resultJsonFactory,
        Payfullapi $helper,
        HistoryFactory $historyFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Encryption\Encryptor $crypt,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        parent::__construct($context);
        $this->helper = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_historyFactory = $historyFactory;
        $this->_scopeConfig = $scopeConfig;   
        $this->_crypt = $crypt;
        $this->checkoutSession = $checkoutSession;
    }
    /**
     * @inheritdoc
     */
    public function execute()
    {
        // echo "ssss";exit;
        // print_r($_POST);
        $apiUname = $this->_scopeConfig->getValue('payment/payfull/merchant_gateway_username');
        if (!$apiUname) {
            throw new LocalizedException(__('No Api username set. transaction will not proceed.'));
        }
        
        $password = $this->_scopeConfig->getValue('payment/payfull/merchant_gateway_password');
        if (!$password) {
            throw new LocalizedException(__('No Password api set. transaction will not proceed.'));
        }

        $api_url = $this->_scopeConfig->getValue('payment/payfull/merchant_gateway_url');
        if (!$api_url) {
            throw new LocalizedException(__('No URL api set. transaction will not proceed.'));
        }
        $apiUname = $this->_crypt->decrypt($apiUname);
        $password = $this->_crypt->decrypt($password);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');

        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');

        $grandTotal = $cart->getQuote()->getGrandTotal();
        $getClientIp = $this->helper->getClientIp();
        $baseUrl = $storeManager->getStore()->getBaseUrl();
        $defaults = array(
            "bank_id"         => 'BKMExpress',
            "return_url"      => $baseUrl.'/payfull/payment/ReturnBKM' 
        );
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $defaults = array_merge($defaults, $_POST);
        $params = $this->helper->_createParmListSale($apiUname, $defaults, '');

        $this->result = $this->helper->bindCurl($params, $password, $api_url);
        $this->result = json_decode($this->result);
        // echo "ddddd";        
        // var_dump($this->result);exit;
        if(is_string($this->result) && strpos($this->result, '<html')) {
            $this->checkoutSession->setPayfull([
                'html'=>$this->result
            ]);
        }
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
        if(isset($this->result) && is_object($this->result)) {
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
            return $resultj->setData($this->result);
        }
        
    }
}
