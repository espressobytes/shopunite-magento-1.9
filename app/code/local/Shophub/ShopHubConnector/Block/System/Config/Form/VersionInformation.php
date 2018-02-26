<?php

class Shophub_ShopHubConnector_Block_System_Config_Form_VersionInformation extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return mixed|string
     */
    protected function _getHeaderHtml($element)
    {
        $headerHtml = parent::_getHeaderHtml($element);
        $debugInfo = array();
        try {
            $shopHubVersion = $this->getExtensionVersion();
        } catch (Exception $e) {
            return '--- <div style="display:none">Exception: ' . $e->getMessage() . '</div>' . $headerHtml;
        }
        $debugInfo[] = "Version: " . $shopHubVersion;

        $headerHtml = str_replace('<table cellspacing="0" class="form-list">', implode("<br/>", $debugInfo) . '<table cellspacing="0" class="form-list">', $headerHtml);
        return $headerHtml;
    }

    /**
     * @return string
     */
    private function getExtensionVersion()
    {
        return (string) Mage::getConfig()->getNode()->modules->Shophub_ShopHubConnector->version;
    }

}