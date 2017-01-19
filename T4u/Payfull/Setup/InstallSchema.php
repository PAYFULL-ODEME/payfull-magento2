<?php
 
namespace T4u\Payfull\Setup;
 
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Quote\Setup\QuoteSetupFactory;
use Magento\Sales\Setup\SalesSetupFactory;
 
class InstallSchema implements InstallSchemaInterface
{
	 /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * Category setup factory
     *
     * @var CategorySetupFactory
     */
    protected $categorySetupFactory;

    /**
     * Quote setup factory
     *
     * @var QuoteSetupFactory
     */
    protected $quoteSetupFactory;

    /**
     * Sales setup factory
     *
     * @var SalesSetupFactory
     */
    protected $salesSetupFactory;
	
	 /**
     * Init
     *
     * @param CategorySetupFactory $categorySetupFactory
     * @param SalesSetupFactory $salesSetupFactory
     */
    public function __construct(
        SalesSetupFactory $salesSetupFactory
    ) {
        $this->salesSetupFactory = $salesSetupFactory;
    }

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        
        // Get customer_product_info table
        $tableName = $installer->getTable('order_transaction_info');
        // Check if the table already exists
        if ($installer->getConnection()->isTableExists($tableName) != true) {
            // Create customer_product_info table
            $table = $installer->getConnection()
                ->newTable($tableName)
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                    ],
                    'ID'
                )
                ->addColumn(
                    'store_id',
                    Table::TYPE_SMALLINT,
                    null,                 
                    ['nullable' => false, 'default' => '1'],
                    'Store Id'
                )
                ->addColumn(
                    'order_id',
                    Table::TYPE_INTEGER,
					64,                 
                    [],
                    'Order Id'
                )
                ->addColumn(
                    'transaction_id',
                    Table::TYPE_TEXT,
                    255,            
                    [],
                    'Transaction Id'
                )
                ->addColumn(
                    'total',
                    Table::TYPE_DECIMAL,
                    '12,4',                  
                    [],
                    'Total'
                )
                ->addColumn(
                    'total_try',
                    Table::TYPE_DECIMAL,
                    '12,4',                  
                    [],
                    'Total(TRY)'
                )
                ->addColumn(
                    'conversion_rate',
                    Table::TYPE_DECIMAL,
                    '12,4',
                    [],
                    'Conversion Rate'
                )
                ->addColumn(
                    'commission_total',
                    Table::TYPE_DECIMAL,
                    '12,4',                  
                    [],
                    'Commission Total'
                )
                ->addColumn(
                    'bank_id',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'Bank'
                )
                ->addColumn(
                    'use3d',
                    Table::TYPE_BOOLEAN,
                    null,
                    [],
                    '3D Secure'
                )
                ->addColumn(
                    'client_ip',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'Client Ip'
                )
                ->addColumn(
                    'installments',
                    Table::TYPE_SMALLINT,
                    null,
                    [],
                    'Installments'
                )
                ->addColumn(
                    'extra_installments',
                    Table::TYPE_SMALLINT,
                    null,
                    [],
                    'Extra Installments'
                )
                ->addColumn(
                    'status',
                    Table::TYPE_BOOLEAN,
                    null,
                    ['nullable' => false],
                    'Status'
                )
                ->addColumn(
                    'date_added',
                    Table::TYPE_DATETIME,
                    null,
                    [],
                    'Date Added'
                )
                ->addColumn(
                    'created_at',
                    Table::TYPE_DATETIME,
                    null,
                    [],
                    'Created At'
                )
                ->setOption('type', 'InnoDB')
                ->setOption('charset', 'utf8');
            $installer->getConnection()->createTable($table);
        }
		$installer->endSetup();
		/** @var \Magento\Sales\Setup\SalesSetup $salesSetup */
        // $salesSetup = $this->salesSetupFactory->create(['setup' => $setup]);


        /**
         * Remove previous attributes
         */
        // $attributes =       ['payfull_commission'];
        // foreach ($attributes as $attr_to_remove){
        //     $salesSetup->removeAttribute(\Magento\Sales\Model\Order::ENTITY,$attr_to_remove);

        // }
        $installer = $setup;
        $installer->startSetup();
        /**
         * Add 'payfull_commission' attributes for order
         */
        $options = ['type' => 'text', 'visible' => false, 'required' => false, 'comment' => 'Payfull Commission'];

        $installer->getConnection()->addColumn($installer->getTable("sales_order"), "payfull_commission", $options);
        
        // $salesSetup->addAttribute('order', 'payfull_commission', $options);
 
        $installer->endSetup();
    }
}