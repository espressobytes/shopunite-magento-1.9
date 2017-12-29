<?php

class Shophub_ShopHubConnector_Model_Catalog_Product_Validator extends Mage_Catalog_Model_Api2_Product_Validator_Product
{

    /** @var array */
    protected $modifiedData;

    /** @var array */
    protected $modifiedMultiselectAttributeValues;

    /** @var Shophub_ShopHubConnector_Helper_Attributes_AttributeOptionHelper */
    protected $attributeOptionHelper;

    /**
     * @param $data
     * @return array
     */
    public function getModifiedData($data)
    {
        if ($this->modifiedData) {
            return $this->modifiedData;
        }
        return $data;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function isValidData(array $data)
    {
        $this->attributeOptionHelper = Mage::helper('shophubconnector/attributes_attributeOptionHelper');
        $this->modifiedData = $data;
        $this->modifiedMultiselectAttributeValues = [];

        $isSatisfied = parent::isValidData($data);

        $errors = $this->getErrors();
        foreach ($errors as $key => $error) {
            if ($this->isAttributeMultiselectValueError($error, $data)) {
                unset($this->_errors[$key]);
                continue;
            }
            if ($this->isAttributeValueError($error)) {
                unset($this->_errors[$key]);
            }
        }
        $isSatisfied = count($this->getErrors()) == 0;
        return $isSatisfied;
    }

    /**
     * @param $error
     * @param $data
     * @return bool
     */
    protected function isAttributeMultiselectValueError($error, $data)
    {
        $errorDetails = $this->getErrorDetailsFromErrorMsg($error);
        if (!is_array($errorDetails)) return false;
        list($attributeCode, $attributeValueLabel) = $errorDetails;

        if (!isset($this->modifiedData[$attributeCode])) {
            return false;
        }

        if (in_array($attributeCode, $this->modifiedMultiselectAttributeValues)) {
            return true;
        }

        $attribute = $this->attributeOptionHelper->getAttributeByCode($attributeCode);
        if (!isset($data[$attributeCode]) || !$attribute) return false;
        if (!is_array($data[$attributeCode]) || !($attribute->getBackendModel() == "eav/entity_attribute_backend_array")) return false;

        $valueArr = [];
        foreach ($data[$attributeCode] as $multiselectValueLabel) {
            $valueArr[] = $this->attributeOptionHelper->getValueIdFromAttributeValueLabel($attributeCode, $multiselectValueLabel, true);
        }

        $this->modifiedData[$attributeCode] = $valueArr;
        $this->modifiedMultiselectAttributeValues[] = $attributeCode;

        return true;
    }

    /**
     * @param $error
     * @return bool
     */
    protected function isAttributeValueError($error)
    {
        $errorDetails = $this->getErrorDetailsFromErrorMsg($error);
        if (!is_array($errorDetails)) return false;
        list($attributeCode, $attributeValueLabel) = $errorDetails;
        $this->modifyAttributeValue($attributeCode, $attributeValueLabel);
        return true;
    }

    /**
     * @param $error
     * @return array|null
     */
    private function getErrorDetailsFromErrorMsg($error)
    {
        $matches = array();
        preg_match('/Invalid value "(.*)" for attribute "(.*)"./', $error, $matches);
        if (isset($matches[0]) && isset($matches[1]) && isset($matches[2])) {
            $attributeValueLabel = $matches[1];
            $attributeCode = $matches[2];
            return [$attributeCode, $attributeValueLabel];
        }
        return null;
    }

    /**
     * @param $attributeCode
     * @param $attributeValueLabel
     * @return bool
     */
    protected function modifyAttributeValue($attributeCode, $attributeValueLabel)
    {
        if (isset($this->modifiedData[$attributeCode])) {
            $valueId = $this->attributeOptionHelper->getValueIdFromAttributeValueLabel($attributeCode, $attributeValueLabel, true);
            $this->modifiedData[$attributeCode] = $valueId;
            return true;
        }
        return false;
    }

}