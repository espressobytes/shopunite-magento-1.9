<?php

class Shophub_ShopHubConnector_Model_AccessToken extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('shophubconnector/accessToken');
    }
}
