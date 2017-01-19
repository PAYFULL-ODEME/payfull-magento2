<?php
/**
 * See COPYING.txt for license details.
 */
namespace T4u\Payfull\Model;



/**
 * Pay In Store payment method model
 */
class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'payfull';
    // protected $_formBlockType = 'payfull/form_checkout';
    // protected $_infoBlockType = 'payfull/info_payment';

    protected $_canVoid = false;
    protected $_canOrder = true;
    protected $_canRefund = true;
    protected $_canCapture = true;
    protected $_canAuthorize = false;
    protected $_canRefundInvoicePartial     = true;
    protected $_canCapturePartial= true;
    protected $_canUseCheckout = true;
    protected $_canSaveCc = false;



  

}
