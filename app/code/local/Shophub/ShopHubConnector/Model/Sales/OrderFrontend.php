<?php

class Shophub_ShopHubConnector_Model_Sales_OrderFrontend extends Mage_Sales_Model_Order
{

    /**
     * @return string
     */
    public function getStatusLabel()
    {
        try {
            return $this->getExternalStatusLabel();
        } catch (Exception $e) {
            Mage::helper('shophubconnector')->logException($e);
        }
        return parent::getStatusLabel();
    }

}