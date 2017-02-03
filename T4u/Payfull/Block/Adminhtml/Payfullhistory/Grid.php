<?php
namespace T4u\Payfull\Block\Adminhtml\Payfullhistory;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var \T4u\Payfull\Model\QuestionFactory
     */
    protected $_historyFactory;
	
	 /**
     * @var \T4u\Payfull\Model\Status
     */
    protected $_status;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \T4u\Payfull\Model\HistoryFactory $HistoryFactory     * 
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \T4u\Payfull\Model\HistoryFactory $historyFactory,       
        \Magento\Framework\Module\Manager $moduleManager,
        array $data = []
    ) {
        $this->_historyFactory = $historyFactory;       
        $this->moduleManager = $moduleManager;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('postGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('post_filter');
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->_historyFactory->create()->getCollection();
        $this->setCollection($collection);

        parent::_prepareCollection();
        return $this;
    }

    /**
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'id',
            [
                'header' => __('ID'),
                'type' => 'number',
                'index' => 'id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
                'name'=>'id'
            ]
        );
        $this->addColumn(
            'order_id',
            [
                'header' => __('Order No'),
                'index' => 'order_id',
                'class' => 'xxx',
                'name'=>'order_id'
            ]
        );
		$this->addColumn(
            'transaction_id',
            [
                'header' => __('Transaction Id'),
                'index' => 'transaction_id',
                'class' => 'xxx',
                'name'=>'transaction_id'
            ]
        );
		$this->addColumn(
            'total',
            [
                'header' => __('Total'),
                'index' => 'total',
                'name'=>'total'
            ]
        );
		$this->addColumn(
            'total_try',
            [
                'header' => __('Total (Try)'),
                'index' => 'total_try',
                'class' => 'xxx',
                'name'=>'total_try'
            ]
        );
		
		$this->addColumn(
            'conversion_rate',
            [
                'header' => __('Conversion Rate'),
                'index' => 'conversion_rate',
                'class' => 'xxx',
                'name'=>'conversion_rate'
            ]
        );
		
		$this->addColumn(
            'bank_id',
            [
                'header' => __('Bank'),
                'index' => 'bank_id',
                'class' => 'xxx',
                'name'=>'bank_name'
            ]
        );
		
		$this->addColumn(
            'use3d',
            [
                'header' => __('3D Secure'),
                'index' => 'use3d',
                'class' => 'xxx',
                'name'=>'threed_secure'
            ]
        );
        
		$this->addColumn(
            'client_ip',
            [
                'header' => __('Client IP'),
                'index' => 'client_ip',
                'class' => 'xxx',
                'name'=>'client_ip'
            ]
        );
		
		$this->addColumn(
            'installments',
            [
                'header' => __('Installments'),
                'index' => 'installments',
                'class' => 'xxx',
                'name'=>'installments'
            ]
        );
		
		$this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'index' => 'status',
                'class' => 'xxx',
                'name'=>'status'
            ]
        );
		$this->addColumn(
            'date_added',
            [
                'header' => __('Date added'),
                'index' => 'date_added',
                'class' => 'xxx',
                'name'=>'date_added'
            ]
        );

        $block = $this->getLayout()->getBlock('grid.bottom.links');
        if ($block) {
            $this->setChild('grid.bottom.links', $block);
        }

        return parent::_prepareColumns();
    }

    
    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('payfull/*/grid', ['_current' => true]);
    }

    /**
     * @param \T4u\Payfull\Model\Payfullhistory|\Magento\Framework\Object $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl(
            'payfull/*/edit',
            ['id' => $row->getId()]
        );
    }
}