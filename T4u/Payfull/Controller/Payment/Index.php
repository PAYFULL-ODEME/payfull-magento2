<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace T4u\Payfull\Controller\Payment;

use Magento\Braintree\Gateway\Command\GetPaymentNonceCommand;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Webapi\Exception;
use Magento\Theme;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use T4u\Payfull\Helper\Payfullapi;
use Magento\Sales\Model\Order;

/**
 * Class GetNonce
 */
class Index extends Action
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SessionManagerInterface
     */
    private $session;

    /**
     * @var GetPaymentNonceCommand
     */
    private $command;

    private $_order;
	private $result;

    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param SessionManagerInterface $session
     * @param GetPaymentNonceCommand $command
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        SessionManagerInterface $session,
        GetPaymentNonceCommand $command,
        JsonFactory    $resultJsonFactory,
        Order $order,
		Payfullapi $helper
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->session = $session;
        $this->command = $command;
        $this->_order = $order;
		$this->helper = $helper;
		$this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $cc_no='';		
        if(!isset($_POST['cc'])){			
                return false;			
        }
        if(isset($_POST['cc'])){
                $cc_no = $_POST['cc'];
        }
        if(strlen($cc_no<6)){
               // return false;
        }
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $issuer = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $installment = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        
        if($cc_no){
               $this->result = $this->helper->send($cc_no, 'Issuer');              
        }
        $resultj = $this->resultJsonFactory->create();
        return $resultj->setData($this->result); 
               
    }
    
}
