<?php

class Shophub_ShopHubConnector_Block_Adminhtml_Apilog extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {
        parent::__construct();
        $this->_blockGroup = 'shophubconnector';
        $this->_controller = 'adminhtml_apilog';
        $this->_headerText = 'ShopUnite Api Log';

    }

}