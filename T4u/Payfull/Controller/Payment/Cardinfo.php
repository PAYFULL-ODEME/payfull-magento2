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

    protected $_orderFactory;

    private $result;

    private $helper;

    protected $checkoutSession;    

    public function __construct(
        Context $context,
        JsonFactory    $resultJsonFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        Payfullapi $helper,
        HistoryFactory $historyFactory,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        parent::__construct($context);
        $this->helper = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_orderFactory = $orderFactory;
        $this->_historyFactory = $historyFactory;
        $this->checkoutSession = $checkoutSession;
    }
    /**
     * @inheritdoc
     */
    public function execute()
    {
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
        if($installment > 1){
            $this->result = $this->helper->sendSale($defaults, 'SaleInstallment');
        }else{            
            $this->result = $this->helper->sendSale($defaults, 'Sale');
        }
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');

        $grandTotal = $cart->getQuote()->getGrandTotal();

        /*$this->checkoutSession->setPayfull(['grandTotal'=>$grandTotal]);*/

        $getClientIp = $this->helper->getClientIp();


        $resultj = $this->resultJsonFactory->create();

        $historyModel = $this->_historyFactory->create();

        $collection = $historyModel->getCollection();        
        $field = array();
        $logdata = array();

        foreach ($collection->getData() as $data ) 
        {            
            $field = array_keys($data);
            
            break;
        }
        //  add in if last -> && $this->result->status === true
        /*if(is_object($this->result))*/
        // response in html when we use 3D_Secure
        // var_dump($this->result);exit;
        if (is_string($this->result) && strpos($this->result, '<html')) {
            $this->checkoutSession->setPayfull([
                'secure'=>true,
                'html'=>$this->result
            ]);
        } else if (isset($this->result) && is_object($this->result)) {
            
            foreach ($this->result as $key => $value) {
               
                foreach ($field as $keys) 
                {   
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
                    }elseif($key == 'store_id'){                        
                        $logdata['store_id']='1';
                         break;
                    }elseif($key == 'transaction_id'){                        
                        $logdata['transaction_id']=$value;
                         break;
                    }elseif($key == 'total_try'){                        
                        $logdata['total_try']=$value;
                         break;
                    }elseif($key == 'conversion_rate'){                        
                        $logdata['conversion_rate']=$value;
                         break;
                    }elseif($key == 'bank_id'){                        
                        $logdata['bank_id']=$value;
                         break;
                    }elseif($key == 'use3d'){ 
                        if($value == 0){
                            $logdata['use3d']='No';
                        }else{
                            $logdata['use3d']='Yes';
                        }                       
                        break;
                    }elseif($key == 'installments'){                        
                        $logdata['installments']=$value;
                         break;
                    }elseif($key == 'extra_installments'){                        
                        $logdata['extra_installments']=$value;
                         break;
                    }elseif($key == 'status'){ 
                        if($value == 0){
                            $logdata['status']='Failed';
                        }else{
                            $logdata['status']='Complete';
                        }
                        break;
                    }elseif($key == 'time'){                        
                        $logdata['date_added']=$value;
                        break;
                    }
                }
            }
            $logdata['client_ip']=$getClientIp;
            $this->checkoutSession->setPayfulllog($logdata);
            return $resultj->setData($this->result);
        }
        
    }
   
}
