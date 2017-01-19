<?php
 
namespace T4u\Payfull\Model\Config\Source;
 
class Customdropdown implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
 
        return [
            ['value' => __('tr'), 'label' => __('Turkey')],
            ['value' => __('en'), 'label' => __('English')]
        ];
    }
}