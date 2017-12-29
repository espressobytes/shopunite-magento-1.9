<?php

class Shophub_ShopHubConnector_Model_Mysql4_ApiLog extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('shophubconnector/apiLog', 'id');
    }
}
