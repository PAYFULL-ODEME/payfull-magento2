<?php

namespace T4u\Payfull\Model;
use T4u\Payfull\Helper\Payfullapi;

class PayfullConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
	const CODE = 'payfull';

    protected $method;

    /**
     * Payment ConfigProvider constructor.
     * @param \Magento\Payment\Helper\Data $paymentHelper
     */
    public function __construct(
        // \Magento\Payment\Helper\Data $paymentHelper,
        Payfullapi $helper
    ) {
        // $this->method = $paymentHelper->getMethodInstance(self::CODE);
        $this->helper = $helper;
    }
	/**
 	* {@inheritdoc}
	*/
    public function getConfig()
    {
    	 $config = ['payment' => ['payfull' => [
    	 'threed_secure' => $this->get3DSecure(),
    	 'bkm_express' => $this->getBKM(),
         'minimum_order' => $this->getMinOrderTotal(),
         'maximum_order' => $this->getMaxOrderTotal(),
         'installment' => $this->getInstallment()
    	 ]]];
        return $config;
    	// echo "sssssssssss";exit;
        // return $this->method->isAvailable() ? ['payment' => ['payfull' => ['threed_secure' => $this->get3DSecure()],],] : [];

        // return [
            // 'key' =&gt; 'value' pairs of configuration
        // ];
    }
    /**
	* Get config from admin
	*/
	public function get3DSecure()
	{
	    return $this->helper->get3DSecure();
	}

    public function getMinOrderTotal()
    {
        return $this->helper->getMinOrderTotal();
    }

    public function getMaxOrderTotal()
    {
        return $this->helper->getMaxOrderTotal();
    }
	
	public function getBKM()
	{
	    return $this->helper->getBKM();
	}
    public function getInstallment()
    {
        return $this->helper->getInstallment();
    }
}