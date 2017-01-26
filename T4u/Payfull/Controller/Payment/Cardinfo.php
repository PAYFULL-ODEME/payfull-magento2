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

    protected $checkoutSession;    

    public function __construct(
        Context $context,
        JsonFactory    $resultJsonFactory,
        Payfullapi $helper,
        HistoryFactory $historyFactory,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        parent::__construct($context);
        $this->helper = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_historyFactory = $historyFactory;
        $this->checkoutSession = $checkoutSession;
    }
    /**
     * @inheritdoc
     */
    public function execute()
    {
         // header('Location: http://35.163.157.163/payfull/payment/RedirectAction');
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
        if(isset($_POST['cc_number'])){
            $installment = $_POST['installments'];
        }
        if(strlen($cc_number<16)){
            return false;
        }
        $resultRedirect  = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if($installment > 1){
            $this->result = $this->helper->sendSale($defaults, 'SaleInstallment');
        }else{            
            $this->result = $this->helper->sendSale($defaults, 'Sale');
        }
        // response in html when we use 3D_Secure
        // echo $this->result;exit;
        if(is_string($this->result) && strpos($this->result, '<html')) {
            $this->checkoutSession->setPayfull([
                'secure'=>true,
                'html'=>$this->result
            ]);
            /*$resultRedirect->setPath('payfull/payment/RedirectAction');
            return $resultRedirect;*/   
        }
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');

        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');

        $grandTotal = $cart->getQuote()->getGrandTotal();
        $getClientIp = $this->helper->getClientIp();


        $resultj = $this->resultJsonFactory->create();

        $historyModel = $this->_historyFactory->create();

        $collection = $historyModel->getCollection();        
        $field = array();
        $logdata = array();
        
        foreach ($collection->getData() as $data ) 
        {            
            $field = array_keys($data);
            $logdata = $data;
            break;
        }
        //  add in if last -> && $this->result->status === true
        /*if(is_object($this->result))*/
        if(isset($this->result) && is_object($this->result)) {
            foreach ($this->result as $key => $value) {
                foreach ($field as $keys) 
                {
                    if($key == $keys){
                        if($key == 'total'){
                            $commission_total = $value - $grandTotal;
                            /*echo $value."gggg".$grandTotal."hhhh".$commission_total;*/
                            $this->checkoutSession->setPayfull(['payfull_commission'=>$commission_total]);
                            $payfull = $this->checkoutSession->getPayfull();
                            $historyModel->setData('commission_total',$commission_total);
                            $logdata['commission_total']=$commission_total;
                        }
                        $historyModel->setData($key,$value);
                        break;
                    }elseif($key == 'time'){
                        $date = explode(' ', $value);
                        $historyModel->setData('date_added',$date['0']);                       
                    }elseif($keys == 'order_id'){
                       // $historyModel->setData('order_id',$order_id);
                    }elseif($keys == 'client_ip'){
                        $historyModel->setData($keys,$getClientIp);
                        $logdata['client_ip']=$getClientIp;
                    }
                }
            }
            $this->checkoutSession->setPayfulllog($logdata);
            //$historyModel->save();
            return $resultj->setData($this->result);
        }
    }
   
}
