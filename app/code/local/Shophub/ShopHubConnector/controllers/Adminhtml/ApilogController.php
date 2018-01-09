<?php

class Shophub_ShopHubConnector_Adminhtml_ApilogController extends Mage_Adminhtml_Controller_Action
{

    protected function _initAction()
    {
        /*
        $this->loadLayout()
            ->_setActiveMenu('shophubconnector/apilog')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Profile Manager'), Mage::helper('adminhtml')->__('Profile Manager'));
        */
        $this->loadLayout();
        return $this;
    }

    /**
     * http://www.magentoshop.com/shophubconnector/apilog/index/
     */
    public function indexAction()
    {
        $this->_initAction();
        $layout = $this->getLayout();
        $block = $layout->createBlock('shophubconnector/adminhtml_apilog');
        $this->_addContent($block);
        $this->renderLayout();
    }

    /**
     * http://www.magentoshop.com/shophubconnector/apilog/grid/
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('shophubconnector/adminhtml_apilog_grid')->toHtml()
        );
    }

    /**
     * http://www.magentoshop.com/shophubconnector/apilog/show/id/x/
     */
    public function showAction()
    {
        $id = $this->getRequest()->getParam('id');
        $apiLogEntity = Mage::getModel('shophubconnector/apiLog')->load($id);

        if ($apiLogEntity) {

            Mage::register('shophubconnector_apilog_data', $apiLogEntity);
            $this->loadLayout();
            $this->_addContent($this->getLayout()->createBlock('shophubconnector/adminhtml_apilog_showItem'));
            $this->renderLayout();

        } else {

            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('shophubconnector')->__('Item does not exist'));
            $this->_redirect('*/*/');

        }
    }

}