<?php

class Shophub_ShopHubConnector_Helper_Catalog_Products extends Shophub_ShopHubConnector_Helper_Data
{

    /** @var Shophub_ShopHubConnector_Helper_Attributes_Attributehelper */
    protected $attributeHelper;

    /** @var Shophub_ShopHubConnector_Helper_Attributes_AttributeOptionHelper */
    protected $attributeOptionHelper;

    /** @var Shophub_ShopHubConnector_Helper_Catalog_Products_ProductDataConnector */
    protected $productDataConnector;

    /** @var Shophub_ShopHubConnector_Helper_Catalog_Products_ImageConnector */
    protected $imageConnector;

    /**
     * Shophub_ShopHubConnector_Helper_Catalog_Products constructor.
     */
    public function __construct()
    {
        $this->attributeHelper = Mage::helper('shophubconnector/attributes_attributeHelper');
        $this->attributeOptionHelper = Mage::helper('shophubconnector/attributes_attributeOptionHelper');
        $this->productDataConnector = Mage::helper('shophubconnector/catalog_products_productDataConnector');
        $this->imageConnector = Mage::helper('shophubconnector/catalog_products_imageConnector');
    }

    /**
     * @param $productArray
     * @param $configProductSetting
     * @return mixed
     */
    public function createOrUpdateConfigurableProduct($productArray, $configProductSetting, $images = array())
    {
        // $usedProductAttributeIdsTest = $productArray['setUsedProductAttributeIds'];
        $usedProductAttributeIds = $this->getAttributeIdsForConfig($configProductSetting);
        $attributeSetId = 4;

        Mage::app()->setCurrentStore(Mage::getModel('core/store')->load(Mage_Core_Model_App::ADMIN_STORE_ID));
        $product = Mage::getModel('catalog/product');
        $productId = $product->getIdBySku($productArray['sku']);
        if (!$productId) {

            $product->setTypeId('configurable');
            $product->setAttributeSetId($attributeSetId);
            $product->getTypeInstance()->setUsedProductAttributeIds($usedProductAttributeIds);

            $typeInstance = $product->getTypeInstance();

            $configurableAttributes = $typeInstance->getConfigurableAttributes();
            $configurableAttributesData = $typeInstance->getConfigurableAttributesAsArray();

            $product->setCanSaveConfigurableAttributes(true);

            $configurableAttributesData = $this->productDataConnector->setUsedDefaultForConfigAttrData($configurableAttributesData, true);
            $product->setConfigurableAttributesData($configurableAttributesData);

            $product->setStockData(array(
                    'use_config_manage_stock' => 0, //'Use config settings' checkbox
                    'manage_stock' => 1, //manage stock
                    'is_in_stock' => 1, //Stock Availability
                )
            );

            $product
                ->setCreatedAt(strtotime('now'))
                ->setStoreId(0)
                ->setSku($productArray['sku'])
                ->setName($productArray['name'])
                ->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                ->setMediaGallery(array('images' => array(), 'values' => array())) //media gallery initialization
            ;

            // set visibility and websitesIDs
            $product = $this->productDataConnector->setVisibilityFromProductArray($productArray, $product);
            $product = $this->productDataConnector->setWebsiteIdsForProduct($productArray, $product);

            $this->productDataConnector->updateProductData($productArray, $product, true);

            $product->save();

        } else {
            $product = Mage::getModel('catalog/product')->load($productId);
            $this->productDataConnector->updateProductData($productArray, $product, false);
        }

        $this->imageConnector->importImages($product, $images);
        
        return $product;
    }

    /**
     * @param $productArray
     * @param $configProductSetting
     */
    public function addConfigAttrValuesToProductData(&$productArray, $configProductSetting)
    {
        $configSubData = $this->getConfigSubData($configProductSetting);
        foreach ($configSubData as $attrValueData) {
            $attributeCode = $this->attributeHelper->getAttributeCodeById($attrValueData['attribute_id']);
            $productArray[$attributeCode] = $attrValueData['value_index'];
        }
    }

    /**
     * @param $simpleProductId
     * @param $configProductSetting
     * @return bool
     * @throws Exception
     */
    public function connectSimpleToConfigProduct($simpleProductId, $configProductSetting)
    {
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        $configAttributeIds = $this->getAttributeIdsForConfig($configProductSetting);
        $configSubData = $this->getConfigSubData($configProductSetting);

        if (isset($configProductSetting['parent_product_sku'])) {

            // $simpleProductId = $simpleProduct->getId();

            $productModel = Mage::getModel('catalog/product');
            $configProduct = $productModel->load($productModel->getIdBySku($configProductSetting['parent_product_sku']));

            $configurableProductsData = $this->productDataConnector->getConfigurableProductData($configProduct, $configAttributeIds);
            $configurableProductsData[$simpleProductId] = $configSubData;

            // $configProduct->setCanSaveConfigurableAttributes(true);
            $configProduct->setConfigurableProductsData($configurableProductsData);
            $configProduct->save();
        }
        return true;
    }

    /**
     * @param $simpleProductSku
     * @param $configProductSetting
     * @return bool
     */
    public function connectSimpleToConfigProductBySku($simpleProductSku, $configProductSetting)
    {
        $simpleProductId = Mage::getModel('catalog/product')->getIdBySku($simpleProductSku);
        return $this->connectSimpleToConfigProduct($simpleProductId, $configProductSetting);
    }

    /**
     * @param $simpleProductData
     * @param $configProductSetting
     * @return bool
     */
    public function connectSimpleToConfigProductByProductData($simpleProductData, $configProductSetting)
    {
        if (isset($simpleProductData['sku'])) {
            return $this->connectSimpleToConfigProductBySku($simpleProductData['sku'], $configProductSetting);
        }
        if (isset($simpleProductData['id'])) {
            return $this->connectSimpleToConfigProduct($simpleProductData['id'], $configProductSetting);
        }
        return false;
    }

    /**
     * @param $configProductSetting
     * @return bool|array
     */
    private function getAttributeCodesForConfig($configProductSetting)
    {
        if (!isset($configProductSetting['attributes_for_config'])) {
            return false;
        }
        return $configProductSetting['attributes_for_config'];
    }

    /**
     * @param $configProductSetting
     * @return array|bool
     */
    private function getAttributeCodeAndIdsForConfig($configProductSetting)
    {
        if (!isset($configProductSetting['attributes_for_config'])) return false;
        $attrCodes = $configProductSetting['attributes_for_config'];
        if (sizeof($attrCodes) == 0) return false;
        $attrCodeAndIds = array();
        foreach ($attrCodes as $code) {
            $attributeId = $this->attributeHelper->getAttributeId($code);
            $attrCodeAndIds[$code] = $attributeId;
        }
        return $attrCodeAndIds;
    }

    /**
     * @param $configProductSetting
     * @return array|bool
     */
    private function getAttributeIdsForConfig($configProductSetting)
    {
        $attributeCodes = $this->getAttributeCodesForConfig($configProductSetting);
        if (!$attributeCodes) {
            return false;
        }
        $attributeIds = array();
        foreach ($attributeCodes as $code) {
            $attributeId = $this->attributeHelper->getAttributeId($code);
            if (!$attributeId) {
                return false;
            }
            $attributeIds[] = $attributeId;
        }
        return $attributeIds;
    }

    /**
     * @param $configProductSetting
     * @return array
     */
    private function getConfigSubData($configProductSetting)
    {
        $data = $configProductSetting['attribute_option_labels'];

        $attrCodesAndIdsForConfig = $this->getAttributeCodeAndIdsForConfig($configProductSetting);
        if (!$attrCodesAndIdsForConfig) {
            return array();
        }

        $configSubData = array();
        foreach ($attrCodesAndIdsForConfig as $attrCode => $attrId) {
            if (!isset($data[$attrCode])) continue;
            $optionLabel = $data[$attrCode];
            $valueIndex = $this->attributeOptionHelper->getValueIdFromAttributeValueLabel($attrCode, $optionLabel);
            $configSubData[] = array(
                'attribute_id' => "$attrId",
                'is_percent' => "0",
                'label' => "$optionLabel",
                'pricing_value' => "0",
                'value_index' => "$valueIndex",
            );
        }
        return $configSubData;
    }

}