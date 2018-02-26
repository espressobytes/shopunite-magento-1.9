<?php

class Shophub_ShopHubConnector_Block_Adminhtml_Apilog_ShowItem extends Mage_Adminhtml_Block_Widget_Form
{

    /**
     * @return Mage_Adminhtml_Block_Widget_Form
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset('apilog_form', array('legend' => Mage::helper('shophubconnector')->__('Apilog Entry Information')));

        $fieldset->addField('date_time', 'text', array(
            'label'     => Mage::helper('shophubconnector')->__('Date and Time'),
            'name'      => 'date_time',
            'readonly' => true,
        ));

        $fieldset->addField('route', 'text', array(
            'label' => Mage::helper('shophubconnector')->__('Route'),
            'name' => 'route',
            'readonly' => true,
        ));

        $fieldset->addField('method', 'text', array(
            'label' => Mage::helper('shophubconnector')->__('Method'),
            'name' => 'method',
            'readonly' => true,
        ));

        $fieldset->addField('response_status_code', 'text', array(
            'label' => Mage::helper('shophubconnector')->__('Response Status Code'),
            'name' => 'response_status_code',
            'readonly' => true,
        ));

        $fieldset->addField('parameters', 'editor', array(
            'label' => Mage::helper('shophubconnector')->__('Request'),
            'name' => 'parameters',
            'readonly' => true,
        ));

        $fieldset->addField('response_content', 'editor', array(
            'label' => Mage::helper('shophubconnector')->__('Response Content'),
            'name' => 'response_content',
            'readonly' => true,
        ));

        $fieldset->addField('error_message', 'editor', array(
            'label' => Mage::helper('shophubconnector')->__('Error Message'),
            'name' => 'error_message',
            'readonly' => true,
        ));

        $apiLogData = Mage::getSingleton('adminhtml/session')->getShophubconnectorApilogData();
        $apiLogDataRegistry = Mage::registry('shophubconnector_apilog_data');

        if ($apiLogData) {
            $form->setValues($apiLogData);
            Mage::getSingleton('adminhtml/session')->setShophubconnectorApilogData(null);
        } elseif ($apiLogDataRegistry) {
            $form->setValues($apiLogDataRegistry->getData());
        }

        return parent::_prepareForm();
    }
}