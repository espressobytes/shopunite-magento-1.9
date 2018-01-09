<?php

class Shophub_ShopHubConnector_Model_Observer_GridMassaction extends Mage_Core_Model_Abstract
{

    public function addExportOrderMassAction($observer)
    {
        try {
            $block = $observer->getEvent()->getBlock();
            if(get_class($block) =='Mage_Adminhtml_Block_Widget_Grid_Massaction'
                && $block->getRequest()->getControllerName() == 'sales_order')
            {
                $block->addItem('newmodule', array(
                    'label' => 'Export to ShopUnite',
                    'url' => Mage::app()->getStore()->getUrl('shophubconnector/ordergrid/exportorders'),
                ));
            }
        } catch (Exception $e) {
            // TODO: log error
        }
    }

}