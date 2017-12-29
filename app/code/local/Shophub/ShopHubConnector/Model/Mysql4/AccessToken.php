<?php

class Shophub_ShopHubConnector_Model_Mysql4_AccessToken extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('shophubconnector/accessTokenTable', 'id');
    }
}
