<?php
/**
 * See COPYING.txt for license details.
 */
namespace T4u\Payfull\Model;

use T4u\Payfull\Model\HistoryFactory;
// use Magento\Framework\Controller\Result\JsonFactory;
use T4u\Payfull\Helper\Payfullapi;

class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{
    private $_historyFactory;
    // private $resultJsonFactory;
    private $helper;

    /**
     * Initialize dependencies.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry     
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        HistoryFactory $historyFactory,
        // JsonFactory    $resultJsonFactory,
        Payfullapi $helper
    ) 
    {
        
        $this->_historyFactory = $_historyFactory;
        // $this->resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
    }
    /**
     * Save code
     *
     * @param array
     */
    public function saveData($result){

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');

        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');

        $grandTotal = $cart->getQuote()->getGrandTotal();
        $getClientIp = $this->helper->getClientIp();

        echo $grandTotal."dddd".$getClientIp;

        // $resultj = $this->resultJsonFactory->create();
        $historyModel = $this->_historyFactory->create();
        $collection = $historyModel->getCollection();        
        $field = array();
        foreach ($collection->getData() as $data ) 
        {            
            $field = array_keys($data);
            break;
        }

        echo <'pre'>;
        print_r($field);

        echo <'pre'>;
        print_r($result);

        //  add in if last -> && $result->status === true
        if(isset($result)) {
            echo "enter";
            foreach ($result as $key => $value) {
                foreach ($field as $keys) 
                {
                    if($key == $keys){
                        if($key == 'total'){
                            $commission_total = $value - $grandTotal;
                            $historyModel->setData('commission_total',$commission_total);
                            $logdata['commission_total']=$commission_total;
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
                        $logdata['client_ip']=$getClientIp;
                    }
                }
            }
            $historyModel->save();
            // return $resultj->setData($result);
        }
        
    }

}
