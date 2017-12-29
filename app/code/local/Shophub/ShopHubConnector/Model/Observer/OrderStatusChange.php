<?php

class Shophub_ShopHubConnector_Model_Observer_OrderStatusChange extends Mage_Core_Model_Abstract
{


    public function afterSetOrderState(Varien_Event_Observer $observer)
    {
        $shophubHelper = Mage::helper('shophubconnector');
        $isActive = $shophubHelper->getConfigValue('order_export/is_active');

        if ($isActive) {
            try {
                $order = $observer->getEvent()->getOrder();
                $newState = $observer->getEvent()->getState();

                /** @var $orderExportHelper Shophub_ShopHubConnector_Helper_Connector_OrderExport */
                $orderExportHelper = Mage::helper('shophubconnector/connector_orderExport');
                $orderExportHelper->updateOrderToShopHub($order, $newState);

            } catch (Exception $e) {
                // TODO: log exception
                $msg = $e->getMessage();
            }
        }
    }

}