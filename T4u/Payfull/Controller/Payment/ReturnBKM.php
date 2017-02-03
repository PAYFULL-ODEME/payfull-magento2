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
use T4u\Payfull\Model\HistoryFactory;/**
 * Class Return3D
 */
class ReturnBKM extends Action
{
    protected $_orderFactory; 

    protected $result;

    protected $helper;  

    protected $checkoutSession;

    protected $paymentModel;   

    protected $_historyFactory;

    protected $resultRedirect;

    public function __construct(
        Context $context,
        JsonFactory    $resultJsonFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        Payfullapi $helper,
        \Magento\Checkout\Model\Session $checkoutSession,
        HistoryFactory $historyFactory,
        ResultFactory $resultFactory
    ) {
        parent::__construct($context);
        $this->helper = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_orderFactory = $orderFactory;
        $this->checkoutSession = $checkoutSession;
        $this->_historyFactory = $historyFactory;
        $this->resultRedirect = $resultFactory;
    }
    /**
     * @inheritdoc
     */
    public function execute()
    {
        if(isset($_REQUEST)/*&& $_REQUEST['status'] == '1'*/){
            $result = $_REQUEST;
            
            $resultj = $this->resultJsonFactory->create();

            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

            /*$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

            $cart = $objectManager->get('\Magento\Checkout\Model\Cart');

            $grandTotal = $cart->getQuote()->getGrandTotal();*/
            $getClientIp = $this->helper->getClientIp();

            $historyModel = $this->_historyFactory->create();
            $collection = $historyModel->getCollection();        
            $field = array();
            foreach ($collection->getData() as $data ) 
            {            
                $field = array_keys($data);
                break;
            }
            //  add in if last -> && $result->status === true
            if(isset($result)) {
                foreach ($result as $key => $value) {
                    foreach ($field as $keys) 
                    {
                        if($key == $keys){
                            if($key == 'total'){
                                if($result['original_currency'] == $result['currency']){
                                    $logdata['total']=$value;
                                    $logdata['total_try']=$value;
                                    $commission_total = $value - $result['original_total'];
                                    $this->checkoutSession->setPayfull(['payfull_commission'=>$commission_total]);
                                    $payfull = $this->checkoutSession->getPayfull();
                                    $logdata['commission_total'] = $commission_total;
                                }else{
                                    $total = $value * $result['conversion_rate'];
                                    $logdata['total'] = round($total, 2);
                                    /*$logdata['total'] = $result['original_total'];*/
                                    $logdata['total_try']=$value;
                                    $commission_total = $logdata['total'] - $result['original_total'];
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
                        }elseif($keys == 'client_ip'){
                            $logdata['client_ip']=$getClientIp;
                        }
                    }
                }
                $this->checkoutSession->setPayfulllog($logdata);
                /*$historyModel->setData($logdata);
                $historyModel->save();*/                
            }
            /*echo "BKM Express Payment Successful";*/
            // return $resultj->setData($result);
            $resultRedirect->setPath('checkout/onepage/success');
            return $resultRedirect;
        }else{
            echo $_REQUEST['ErrorMSG'];
        }
    }
}
