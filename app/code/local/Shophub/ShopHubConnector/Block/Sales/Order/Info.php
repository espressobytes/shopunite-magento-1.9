<?php

class Shophub_ShopHubConnector_Block_Sales_Order_Info extends Mage_Sales_Block_Order_Info
{

    protected $orderWithExtInfos;

    /**
     * returns order object with more info of order in external system
     * method $_order->getPackageNumbers() gets the package numbers as a string.
     * That can be used in info.phtml to show the current progress state of the order
     *
     * @return Mage_Sales_Model_Order|Shophub_ShopHubConnector_Model_Sales_OrderFrontend
     */
    public function getOrder()
    {
        $order = parent::getOrder();

        /** @var Shophub_ShopHubConnector_Helper_Connector_ExternalOrderInfo $extOrderInfoHelper */
        $extOrderInfoHelper = Mage::helper('shophubconnector/connector_externalOrderInfo');
        $this->orderWithExtInfos = $extOrderInfoHelper->getOrderObjectWithExternalInfos($order);
        return $this->orderWithExtInfos;
    }

}