<?php

class Shophub_ShopHubConnector_IndexController extends Mage_Core_Controller_Front_Action
{

    /**
     * test if module is installed:
     * http://www.magentoroot.com/shophubconnector/index/test/
     */
    public function testAction()
    {
        $this->getResponse()->setBody("Hello! Module ShopHubConnector is installed!");
    }

}








