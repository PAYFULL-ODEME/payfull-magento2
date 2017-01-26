<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace T4u\Payfull\Helper;

use Magento\Directory\Model\ResourceModel\Country\CollectionFactory;
use Magento\Framework\Encryption\Encryptor;
/**
 * Class Country
 */
class Payfullapi extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Directory\Model\ResourceModel\Country\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var \Magento\Braintree\Model\Adminhtml\System\Config\Country
     */
    private $countryConfig;

    /**
     * @var array
     */
    private $countries;

    private $_scopeConfig;

    private $_crypt;

    public $issuer_bank_id;
	
    public $resultresponse;

    public $grandTotal;

    public $currencyCode;

    public $installment;

    public $bankId;

    public $gateway;

    public $exchangeRate;

    public $baseUrl;
        
    /**
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $factory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $factory,
        \Magento\Braintree\Model\Adminhtml\System\Config\Country $countryConfig,        
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Encryption\Encryptor $crypt
    ) {
        $this->collectionFactory = $factory;
        $this->countryConfig = $countryConfig;
        $this->_scopeConfig = $scopeConfig;   
        $this->_crypt = $crypt;
    }
    
    public function bindCurl($params, $merchantPassword, $api_url)
    {       
        //begin HASH calculation
        ksort($params);
        $hashString = "";
        foreach ($params as $key=>$val) {
            $hashString .= strlen($val) . $val;
        }
        $params["hash"] = hash_hmac("sha1", $hashString, $merchantPassword);
        //end HASH calculation

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $response = curl_exec($ch);
        
        $curlerrcode = curl_errno($ch);
        $curlerr = curl_error($ch);
        /* Check response is json or html */
        if(is_string($response) && strpos($response, '<html')) {
            return json_encode($response);            
        }else{
              return $response;
        }
        
    }
    public function cancelOrder($defaults)
    {
        $getClientIp = $this->getClientIp();
        $language = $this->getLanguage();
        // echo $getClientIp."ssss".$language;
        // var_dump($defaults);exit;
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

        $params = array(           
            "language"        => $language,
            "merchant"        => $apiUname,
            "client_ip"       => $getClientIp
         );
        $params = array_merge($params, $defaults);
        // var_dump($params);exit;
        $response = $this->helper->bindCurl($params, $password, $api_url);

        return $response; 
    }

    public function send($cc_no_first_six, $get_param)
    {   
        $responseList='';

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
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart'); 

        // $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $currency = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $this->currencyCode = $currency->getStore()->getCurrentCurrencyCode();

        $this->grandTotal = $cart->getQuote()->getGrandTotal();

        $params = $this->_createParmList($apiUname,$cc_no_first_six,$get_param);
        // send curl call 
        $response = $this->bindCurl($params, $password, $api_url);
        $getMinOrderTotal = $this->getMinOrderTotal();
        
        // get cart order grandtotal
        
        if($this->getInstallment() == '1'){ 
            if($this->grandTotal >= $getMinOrderTotal){         
                $params = $this->_createParmList($apiUname,$cc_no_first_six,'Installments');            
                $responseList = $this->bindCurl($params, $password, $api_url);                 
                $response = json_decode($response);
                $responseList = json_decode($responseList);
               
                foreach ($responseList->data as $index) {               
                    if($index->bank == $response->data->bank_id){
                        $this->installment = count($index->installments)-1;
                        // echo $this->installment;exit;
                        $this->bankId = $response->data->bank_id;
                        $this->gateway = $index->gateway;
                        $this->resultresponse = $response ;             
                        $this->resultresponse = (object)array_merge((array)$response, (array)$index);
                        if($this->getExtraInstallment() == '1'){
                            $params = $this->_createParmList($apiUname,$cc_no_first_six,'ExtraInstallments');
                            $extraInstallment = $this->bindCurl($params, $password, $api_url);
                            $extraInstallment = json_decode($extraInstallment);
                            $this->exchangeRate = $extraInstallment->data->exchange_rate;
                            
                            $params = $this->_createParmList($apiUname,$cc_no_first_six,'ExtraInstallmentsList');
                            $extraInstallmentList = $this->bindCurl($params, $password, $api_url);
                            $extraInstallmentList = json_decode($extraInstallmentList);
                            $this->resultresponse = (object)array_merge((array)$this->resultresponse, (array)$extraInstallment->data);
                            if(isset($extraInstallment->data->campaigns)){
                                $this->resultresponse = (object)array_merge((array)$this->resultresponse, (array)$extraInstallmentList->data->campaigns);
                            }
                            // foreach ($extraInstallment->data->campaigns as $data->$value) {
                            //     foreach ($extraInstallmentList->data->campaigns as $campaigns) {
                            //         // foreach ($data->campaigns as $value) {
                            //             if($data->campaign_id == $campaigns->campaign_id){
                            //                 $this->resultresponse = (object)array_merge((array)$this->resultresponse, (array)$campaigns);
                            //             }
                            //     }
                            // }
                        }
                    }
                }
            }
        }else{          
            $this->resultresponse = $response;
        }               
        return $this->resultresponse;
    }
    /**
     * Returns param array 
     *
     * @return array
     */ 
    public function _createParmList($apiUname,$cc_no_first_six,$get_param){

        $getClientIp = $this->getClientIp();
        $language = $this->getLanguage();
        $params = array(
            "merchant"        => $apiUname,
            "type"            => 'Get',
            "get_param"       => $get_param,            
            "language"        => $language,
            "client_ip"       => $getClientIp,
         );
        if($get_param == 'Issuer'){
            $cc_no_first_six = substr($cc_no_first_six, 0, 6);
            $extra_params = array("bin" => $cc_no_first_six);
            // $params = array_merge($params, $extra_params);
        }
        if($get_param == 'Installments'){
            $extra_params = array("one_shot_commission" => '0');             
            // $params = array_merge($params, $extra_params);           
        }
        if($get_param == 'ExtraInstallments'){
            $extra_params = array(
                "total"           => $this->grandTotal,
                "currency"        => $this->currencyCode,
                "bank_id"         => $this->bankId,
                "gateway"         => $this->gateway,
                "installments"    => $this->installment);             
            $params = array_merge($params, $extra_params);
            // var_dump($params);exit;           
        }
        if($get_param == 'ExtraInstallmentsList'){
            $extra_params = array(
                "exchange_rate"   => $this->exchangeRate,
                "currency"        => $this->currencyCode);
        }
        $params = array_merge($params, $extra_params);           
   
        return $params;     
    }
    public function sendSale($defaults, $saleType)
    {   
        $responseList='';
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
        // get params 


        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        
        $this->baseUrl = $storeManager->getStore()->getBaseUrl();

        $cart = $objectManager->get('\Magento\Checkout\Model\Cart'); 
        $this->grandTotal = $cart->getQuote()->getGrandTotal();

        // $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $currency = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $this->currencyCode = $currency->getStore()->getCurrentCurrencyCode();

        // echo $baseUrl;exit;



        $params = $this->_createParmListSale($apiUname, $defaults, $saleType);
        
        // send curl call 
        $response = $this->bindCurl($params, $password, $api_url);        
        // get cart order grandtotal     
        // $params = $this->_createParmListSale($apiUname, $defaults, 'SaleInstallment');

        // $responseInstallment = $this->bindCurl($params, $password, $api_url);
        $response = json_decode($response);
        // $responseInstallment = json_decode($responseInstallment);
    
        $this->resultresponse = $response;       
        // $this->resultresponse = $responseInstallment;       
        return $this->resultresponse;
    }
    /**
     * Returns param array 
     *
     * @return array
     */ 
    public function _createParmListSale($apiUname, $defaults, $saleType){
        
        $getClientIp = $this->getClientIp();
        $language = $this->getLanguage();
        // $use3d = $this->get3DSecure();      
        $params = array(
            "merchant"        => $apiUname,
            "type"            => 'Sale',
            // "total"           => $this->grandTotal,
            "currency"        => 'TRY',
            // "installments"    => 1,
            "language"        => $language,//[mandatory]  tr/en
            "client_ip"       => $getClientIp,//[mandatory]
            "payment_title"   => 'test payment title',//[mandatory]
            "customer_firstname" => 'Mohammad',
            "customer_lastname"  => 'Alabed',//[mandatory]
			"customer_email"     => 'mohmmadalabed@gmail.com',//[mandatory]
			"customer_phone"     => '265656565',//[mandatory]
            "customer_tc"        => '12590326514',//[optional]

            "passive_data"  => '####aaaa',//[optional]            
         );
        if(isset($defaults['use3d']) && $defaults['use3d'] == 1){
            $extraParam = array(
                "return_url"      => $this->baseUrl.'/payfull/payment/Return3D' 
            );
            $params = array_merge($params, $extraParam);
        }     
        $params = array_merge($defaults, $params);
        // echo "<pre>";
        // print_r($params);
        // exit; 
        return $params;     
    }

    /**
     * Returns config value
     *
     * @return string
     */
    public function getLanguage(){
        return $this->_scopeConfig->getValue('payment/payfull/language');
    }
    /**
     * Returns config value
     *
     * @return integer
     */
    public function getExtraInstallment(){
        return $this->_scopeConfig->getValue('payment/payfull/extra_installment');
    }
    /**
     * Returns config value
     *
     * @return integer
     */
    public function getInstallment(){
        return $this->_scopeConfig->getValue('payment/payfull/installment');
    }
    /**
     * Returns config value
     *
     * @return integer
     */
    public function getMinOrderTotal(){
        return $this->_scopeConfig->getValue('payment/payfull/minimum_order');
    }
    /**
     * Returns config value
     *
     * @return integer
     */
    public function getMaxOrderTotal(){
        return $this->_scopeConfig->getValue('payment/payfull/maximum_order');
    }
	/**
     * Returns config value
     *
     * @return integer
     */
	public function get3DSecure(){
		return $this->_scopeConfig->getValue('payment/payfull/threed_secure');
	}
	/**
     * Returns config value
     *
     * @return integer
     */
	public function getBKM(){
		return $this->_scopeConfig->getValue('payment/payfull/bkm_express');
	}
	
	public function getClientIp(){
		 $ipaddress = '';
		if (isset($_SERVER['HTTP_CLIENT_IP']))
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else if(isset($_SERVER['HTTP_X_FORWARDED']))
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		else if(isset($_SERVER['HTTP_FORWARDED']))
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		else if(isset($_SERVER['REMOTE_ADDR']))
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		else
			$ipaddress = 'UNKNOWN';		
		return $ipaddress;
	}
}

?>
