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

        $baseUrl = $storeManager->getStore()->getBaseUrl();
        $defaults = array(
            "bank_id"         => 'BKMExpress',
            "return_url"      => $baseUrl.'payfull/payment/ReturnBKM' 
        );

        $resultj = $this->resultJsonFactory->create();

        $defaults = array_merge($defaults, $_POST);
        $params = $this->helper->_createParmListSale($apiUname, $defaults, '');

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');

        $grandTotal = $cart->getQuote()->getGrandTotal();
        $getClientIp = $this->helper->getClientIp();
        
        $historyModel = $this->_historyFactory->create();

        $collection = $historyModel->getCollection();        
        $field = array();
        $logdata = array();

        foreach ($collection->getData() as $data ) 
        {            
            $field = array_keys($data);
            
            break;
        }
        $this->result = $this->helper->bindCurl($params, $password, $api_url);

        $this->result = json_decode($this->result);
        // var_dump($this->result);exit;
        if(is_string($this->result) && strpos($this->result, '<html')) {
            $this->checkoutSession->setPayfull([
                'html'=>$this->result
            ]);
        } else if (isset($this->result) && is_object($this->result)) {
            
            foreach ($this->result as $key => $value) {
               
                foreach ($field as $keys) 
                {   
                    if($key == $keys){
                            if($key == 'total'){
                                if($this->result->original_currency == $this->result->currency){
                                    $logdata['total']=$value;
                                    $logdata['total_try']=$value;
                                    $commission_total = $value - $grandTotal;
                                    $this->checkoutSession->setPayfull(['payfull_commission'=>$commission_total]);
                                    $payfull = $this->checkoutSession->getPayfull();
                                    $logdata['commission_total'] = $commission_total;
                                }else{
                                    $total = $value * $this->result->conversion_rate;
                                    $logdata['total'] = round($total, 2);
                                    $logdata['total_try']=$value;
                                    $commission_total = $logdata['total'] - $grandTotal;
                                    $this->checkoutSession->setPayfull(['payfull_commission'=>$commission_total]);
                                    $payfull = $this->checkoutSession->getPayfull();
                                    $logdata['commission_total'] = $commission_total;
                                }
                                break;
                            }elseif($key == 'status'){ 
                                if($value == 0){
                                    $logdata['status']='Failed';
                                }else{
                                    $logdata['status']='Complete';
                                }
                                break;
                            }elseif($key == 'use3d'){ 
                                if($value == 0){
                                    $logdata['use3d']='No';
                                }else{
                                    $logdata['use3d']='Yes';
                                }                       
                                break;
                            }
                            $logdata[$key]=$value;
                            break;
                        }elseif($key == 'time'){
                            $logdata['date_added']=$value;                      
                        }
                }
            }
            $logdata['client_ip']=$getClientIp;
            $this->checkoutSession->setPayfulllog($logdata);
            return $resultj->setData($this->result);
        }
    }
}
