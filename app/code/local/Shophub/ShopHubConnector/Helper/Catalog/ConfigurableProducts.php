<?php

class Shophub_ShopHubConnector_Helper_Catalog_ConfigurableProducts extends Shophub_ShopHubConnector_Helper_Data
{

    /** @var Shophub_ShopHubConnector_Helper_Attributes_Attributehelper */
    protected $attributeHelper;

    /** @var Shophub_ShopHubConnector_Helper_Attributes_AttributeOptionHelper */
    protected $attributeOptionHelper;

    public function __construct()
    {
        $this->attributeHelper = Mage::helper('shophubconnector/attributes_attributeHelper');
        $this->attributeOptionHelper = Mage::helper('shophubconnector/attributes_attributeOptionHelper');
    }

    public function createConfigurableProduct($data, $productConfiguration)
    {
        $product = Mage::getModel('catalog/product');
        $product->setTypeId('configurable');

        // prepare and do validation
        $sku = $data['sku'];
        if ($product->getIdBySku($sku)) {
            // product already exists
            // TODO: update product if said so in the settings or whereever
            return false;
        };

        $attrIdsForConfigurability = $this->getAttributeIdsForConfig($productConfiguration);
        if (!$attrIdsForConfigurability) {
            // no attributes for configurability set up
            return false;
        }

        $attrSetId = $this->getAttributeSetId($data);

        // basic data
        $product
            ->setStoreId(0)
            ->setSku($sku)
            ->setName($data['name'])
            ->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
            ->setMediaGallery(array('images' => array(), 'values' => array()))//media gallery initialization
            ->setAttributeSetId($attrSetId);

        $product->setVisibility(4);

        // configurable data
        $this->setupConfigurableProduct($product, $attrIdsForConfigurability);

        // set 'manage stocks' and 'is in stock' for configurable product to YES!
        $product->setStockData(array(
                'use_config_manage_stock' => 0, //'Use config settings' checkbox
                'manage_stock' => 1, //manage stock
                'is_in_stock' => 1, //Stock Availability
            )
        );

        $product->setCreatedAt(strtotime('now'));
        $product->setWebsiteIds([1, 2, 3]); // onloom-specific!

        $product->save();
        return $product;

    }

    /**
     * @param $product
     * @param $attrIdsForConfigurability
     */
    private function setupConfigurableProduct(&$product, $attrIdsForConfigurability)
    {
        $product->getTypeInstance()->setUsedProductAttributeIds($attrIdsForConfigurability);
        $typeInstance = $product->getTypeInstance();
        $configurableAttributesData = $product->getTypeInstance()->getConfigurableAttributesAsArray($product);

        $product->setCanSaveConfigurableAttributes(true);
        $configurableAttributesData = $this->setUsedDefaultForConfigAttrData($configurableAttributesData);
        $configurableAttributesData = array (
            0 =>
                array (
                    'id' => NULL,
                    'label' => 'Size',
                    'use_default' => NULL,
                    'position' => NULL,
                    'values' =>
                        array (
                        ),
                    'attribute_id' => '165',
                    'attribute_code' => 'size',
                    'frontend_label' => 'Size',
                    'store_label' => 'Size',
                ),
        );
        $product->setConfigurableAttributesData($configurableAttributesData);
    }

    public function connectSimpleProductToConfigurable($data, $productConfiguration)
    {
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

        $productModel = Mage::getModel('catalog/product');

        $simpleProduct = Mage::getModel('catalog/product')->load($productModel->getIdBySku($data['sku']));
        $simpleProductId = $simpleProduct->getId();

        $configProduct = Mage::getModel('catalog/product')->load($productModel->getIdBySku($productConfiguration['parent_product_sku']));

        $configAttributeIds = $this->getAttributeIdsForConfig($productConfiguration);

        $configurableProductsData = $this->getConfigurableProductData($configProduct, $configAttributeIds);
        $newConfigSubData = $this->getConfigSubData($productConfiguration);

        $configurableProductsData[$simpleProductId] = $newConfigSubData[0];
        $configProduct->setConfigurableProductsData($configurableProductsData);

        $configurableAttributesData = $configProduct->getTypeInstance()->getConfigurableAttributesAsArray();
        $configurableAttributesData[0]['values'][] = $newConfigSubData[0];
        $configProduct->setConfigurableAttributesData($configurableAttributesData);

        $configProduct->setCanSaveConfigurableAttributes(true);
        $configProduct->save();

//        $simpleProduct->save();
    }

    private function getConfigurableProductData($_product, $attrIdArray)
    {

        $configurableProductsData = array();


        /*
        if($_product->getTypeId() == "configurable") {
            $conf = Mage::getModel('catalog/product_type_configurable')->setProduct($_product);
            $simple_collection = $conf->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions();
            foreach($simple_collection as $simpleProduct){

                $simpleProductId = $simpleProduct->getId();
                $configSubData = array();
                foreach ($attrIdArray as $myAttrId ) {

                    // get attribute form Magento resource model:
                    $attr = Mage::getModel('catalog/resource_eav_attribute')->load($myAttrId);
                    $attrCode = $attr->getData('attribute_code');
                    $attrSource = $attr->getSource();
                    $attrOptions = $attrSource->getAllOptions();

                    // get attribute value and label from product:
                    $attrValue = Mage::getResourceModel('catalog/product')->getAttributeRawValue($simpleProductId, $attrCode, 0);
                    foreach ($attrOptions as $myOption) {
                        if ($myOption['value'] == $attrValue) {
                            break;
                        }
                    }
                    $attrLabel = $myOption['label'];

                    // form array for configurable product data:
                    $configSubData[] = array(
                        'attribute_id'  => $myAttrId,
                        'is_percent'    => 0,
                        'pricing_value' => 0,
                        'value_index'   => $attrValue,
                        'label'         => $attrLabel,
                    );
                }

                $configurableProductsData[$simpleProductId] = $configSubData;
            }
        }
        */
        return $configurableProductsData;
    }

    private function getConfigSubData($productConfiguration)
    {
        $data = $productConfiguration['attribute_option_labels'];

        $attrCodesAndIdsForConfig = $this->getAttributeCodeAndIdsForConfig($productConfiguration);
        $configSubData = array();
        foreach ($attrCodesAndIdsForConfig as $attrCode => $attrId) {
            if (!isset($data[$attrCode])) continue;
            $optionLabel = $data[$attrCode];
            $valueIndex = $this->attributeOptionHelper->getValueIdFromAttributeValueLabel($attrCode, $optionLabel);
            $configSubData[] = array(
                'label' => "$optionLabel",
                'attribute_id' => (int)$attrId,
                'value_index' => (int)$valueIndex,
                'is_percent' => 0,
                'pricing_value' => 0,
            );
        }
        return $configSubData;
    }

    private function getAttributeSetId($data)
    {
        // TODO: if no attribute Set is given, choose one! Eventually choose one by an intelligent method that goes through the attributes.
        if (isset($data['attribute_set_id'])) {
            return $data['attribute_set_id'];
        }
        $attrSetId = 4; // TODO: onloom-specific! Should be the Default Attribute Set!
        return $attrSetId;
    }

    /**
     * @param $configurableAttributesData
     * @param $value
     * @return mixed
     */
    public function setUsedDefaultForConfigAttrData($configurableAttributesData, $value)
    {
        foreach ($configurableAttributesData as $key => $attribute) {
            $configurableAttributesData[$key]['use_default'] = $value;
        }
        return $configurableAttributesData;
    }

    /**
     * @param $productConfiguration
     * @return bool|array
     */
    private function getAttributeCodesForConfig($productConfiguration)
    {
        if (!isset($productConfiguration['attributes_for_config'])) {
            return false;
        }
        return $productConfiguration['attributes_for_config'];
    }

    /**
     * @param $productConfiguration
     * @return array|bool
     */
    private function getAttributeCodeAndIdsForConfig($productConfiguration)
    {
        if (!isset($productConfiguration['attributes_for_config'])) return false;
        $attrCodes = $productConfiguration['attributes_for_config'];
        if (sizeof($attrCodes) == 0) return false;
        $attrCodeAndIds = array();
        foreach ($attrCodes as $code) {
            $attributeId = $this->attributeHelper->getAttributeId($code);
            $attrCodeAndIds[$code] = $attributeId;
        }
        return $attrCodeAndIds;
    }

    /**
     * @param $productConfiguration
     * @return array|bool
     */
    private function getAttributeIdsForConfig($productConfiguration)
    {
        $attributeCodes = $this->getAttributeCodesForConfig($productConfiguration);
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


}