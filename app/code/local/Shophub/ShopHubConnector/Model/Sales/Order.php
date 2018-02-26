<?php

class Shophub_ShopHubConnector_Model_Sales_Order extends Mage_Sales_Model_Order
{

    /**
     * @param string $state
     * @param bool $status
     * @param string $comment
     * @param null $isCustomerNotified
     * @param bool $shouldProtectState
     * @return $this
     */
    protected function _setState($state, $status = false, $comment = '', $isCustomerNotified = null, $shouldProtectState = false)
    {
        parent::_setState($state,$status,$comment,$isCustomerNotified,$shouldProtectState);
        Mage::dispatchEvent('sales_order_status_after', array(
            'order' => $this,
            'state' => $state,
            'status' => $status,
            'comment' => $comment,
            'isCustomerNotified' => $isCustomerNotified,
            'shouldProtectState' => $shouldProtectState
        ));
        return $this;
    }

}