<?php

class Shophub_ShopHubConnector_Helper_Attributes_Attributehelper extends Shophub_ShopHubConnector_Helper_Data
{

    /**
     * @param $attributeCode
     * @return bool | int
     */
    public function getAttributeId($attributeCode)
    {
        $attr = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product', $attributeCode);
        if (null !== $attr->getId()) {
            return $attr->getId();
        } else {
            return false;
        }
    }

    /**
     * @param $attributeId
     * @return null | int
     */
    public function getAttributeCodeById($attributeId)
    {
        $attrModel = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);
        if ($attrModel) {
            return $attrModel->getAttributeCode();
        }
        return null;
    }

}