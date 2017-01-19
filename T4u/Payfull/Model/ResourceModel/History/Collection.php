<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace T4u\Payfull\Model\ResourceModel\History;
 
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('T4u\Payfull\Model\History', 'T4u\Payfull\Model\ResourceModel\History');        
    }
}
