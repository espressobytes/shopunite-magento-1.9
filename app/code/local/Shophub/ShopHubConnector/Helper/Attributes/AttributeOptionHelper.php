<?php

class Shophub_ShopHubConnector_Helper_Attributes_AttributeOptionHelper extends Shophub_ShopHubConnector_Helper_Data
{

    /**
     * @param $attributeCode
     * @param $attributeOptionLabel
     * @param $createNewAttributeOptionValue bool
     * @return bool|null|integer
     * @throws Mage_Core_Exception
     */
    public function getValueIdFromAttributeValueLabel($attributeCode, $attributeOptionLabel, $createNewAttributeOptionValue = true)
    {
        $attribute = $this->getAttributeByCode($attributeCode);

        if ($attribute->getSourceModel() == "eav/entity_attribute_source_boolean") {
            return $this->getValueForBooleanAttribute($attributeOptionLabel);
        }

        if (!$attribute->usesSource()) {
            return false;
        }

        $value = $this->getAttributeOptionValueId($attribute, $attributeOptionLabel);
        if (!$value && $createNewAttributeOptionValue) {
            $value = $this->createAttributeOption($attribute, $attributeOptionLabel);

        }
        if ($value) {
            return $value;
        }
        return null;
    }

    /**
     * @param $attributeCode
     * @return mixed
     */
    public function getAttributeByCode($attributeCode)
    {
        /** @var Mage_Eav_Model_Attribute $attribute */
        return Mage::getSingleton('eav/config')->getAttribute('catalog_product', $attributeCode);
    }

    /**
     * @param $attribute
     * @param $optionLabel
     * @return bool
     */
    public function createAttributeOption($attribute, $optionLabel)
    {
        try {
            if ($this->getAttributeOptionValueId($attribute, $optionLabel)) {
                return false;
            }

            $value = array();
            $value['option'] = array($optionLabel, $optionLabel);

            $option = array('value' => $value);
            $attribute->setData('option', $option);
            $attribute->save();

            $attributeOptionsModel = Mage::getModel('eav/entity_attribute_source_table');
            $attributeTable = $attributeOptionsModel->setAttribute($attribute);
            $options = $attributeOptionsModel->getAllOptions(false);

            foreach ($options as $option) {
                if ($option['label'] == $optionLabel) {
                    return $option['value'];
                }
            }

        } catch (Exception $e) {
            $msg = $e->getMessage();
        }

        return false;
    }

    private function getAttributeOptionValueId($attribute, $optionLabel)
    {
        $options = $attribute->getSource()->getAllOptions(false);
        foreach ($options as $option) {
            if ($option['label'] == $optionLabel) {
                return $option['value'];
            }
        }
        return null;
    }

    protected function getValueForBooleanAttribute($label)
    {
        if (is_string($label)) {
            $label = strtolower($label);
        }
        if (in_array($label, [1, 'yes', 'ja'])) {
            return '1';
        }
        return '0';
    }

    public function createAttributeOptionValue($attributeCode, $attributeValueLabel)
    {

        return null;
    }

}