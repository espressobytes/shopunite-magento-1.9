<?php

class Shophub_ShopHubConnector_Block_Sales_Order_History extends Mage_Sales_Block_Order_History
{

    public function __construct()
    {
        parent::__construct();

        /** @var Shophub_ShopHubConnector_Helper_Connector_ExternalOrderInfo $extOrderInfoHelper */
        $extOrderInfoHelper = Mage::helper('shophubconnector/connector_externalOrderInfo');
        try {
            // rewrite order collection to get external order status label
            $extOrderInfoHelper->replaceOrderCollection($this);
        } catch (Exception $e) {
            $extOrderInfoHelper->logException($e);
        }
    }

}