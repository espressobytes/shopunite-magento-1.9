<?php

class Shophub_ShopHubConnector_Helper_Catalog_Products_ProductDataConnector extends Shophub_ShopHubConnector_Helper_Data
{
    protected $lastErrorMsg = null;

    public function getLastErrorMsg()
    {
        return $this->lastErrorMsg;
    }

    public function setUsedDefaultForConfigAttrData($configurableAttributesData, $value)
    {
        $configurableAttributesDataOrig = $configurableAttributesData;
        foreach ($configurableAttributesData as $key => $attribute) {
            $configurableAttributesData[$key]['use_default'] = $value;
        }
        return $configurableAttributesData;
    }

    public function getConfigurableProductData($_product, $attrIdArray)
    {

        $configurableProductsData = array();

        if ($_product->getTypeId() == "configurable") {
            $conf = Mage::getModel('catalog/product_type_configurable')->setProduct($_product);
            $simple_collection = $conf->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions();
            foreach ($simple_collection as $simpleProduct) {

                $simpleProductId = $simpleProduct->getId();
                $configSubData = array();
                foreach ($attrIdArray as $myAttrId) {

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
                        'attribute_id' => $myAttrId,
                        'is_percent' => 0,
                        'pricing_value' => 0,
                        'value_index' => $attrValue,
                        'label' => $attrLabel,
                    );
                }

                $configurableProductsData[$simpleProductId] = $configSubData;

            }
        }
        return $configurableProductsData;
    }

    public function updateProductData($productArray, $product, $productIsNew = false)
    {
        Mage::app()->setCurrentStore(0);

        $avoidKeys = array(
            'sku',
            'status',
            'type_id',
            'shophub_additional_attributes',
            'shophub_method'
        );

        $avoidAttrCode = array(// 'sku'
        );

        foreach ($productArray as $attrCode => $attrValue) {
            if ((!(in_array($attrCode, $avoidKeys))) AND (!(in_array($attrCode, $avoidAttrCode)))) {
                try {
                    $product->setdata($attrCode, $attrValue);
                    if ($productIsNew) {
                        $product->getResource()->saveAttribute($product, $attrCode);
                    }
                } catch (Exception $e) {
                    $msg = $e->getMessage();
                }
            }
        }

        // set visibility & websitesIDs:
        $product = $this->setVisibilityFromProductArray($productArray, $product);
        $product = $this->setWebsiteIdsForProduct($productArray, $product, $productIsNew);

        if (isset($productArray['status'])) {
            $product->setStatus($productArray['status']);
        }
        try {
            $product->save();
            return true;
        } catch (Exception $e) {
            $this->lastErrorMsg = $e->getMessage();
        }
        return false;
    }

    // TODO: delete productArray in params, refactor function
    public function setVisibilityFromProductArray($productArray, $product)
    {

        /*
         * get visibility settings:
         * 0 --> do not touch visibility settings in Magento (Default)
         * 1 --> configurable products and simple products without parents are visible, simple products with parents are invisible
         * 2 --> all products are visible
         * 3 --> configurable products always visible, simple products always not visible
         *
         * visibility knowledge:
         *  VISIBILITY_BOTH = 4
         *  VISIBILITY_IN_CATALOG = 2
         *  VISIBILITY_IN_SEARCH = 3
         *  VISIBILITY_NOT_VISIBLE = 1
         */

        $visibilitySettings = 3;
        if (!(in_array($visibilitySettings, array(0, 1, 2)))) {
            $visibilitySettings == 0;
        }

        if ($visibilitySettings != 0) {
            if ($product->getTypeId() == 'configurable') {

                if (in_array($visibilitySettings, array(1, 2, 3))) {
                    $product->setVisibility(4);
                }

            } else {

                // set visibility:
                if ($visibilitySettings == 2) {
                    $product->setVisibility(4);
                } elseif ($visibilitySettings == 1) {
                    if ($product->getTypeId() == 'simple') {
                        $product->setVisibility(4);
                    } else {
                        $product->setVisibility(1);
                    }
                } elseif ($visibilitySettings == 3) {
                    $product->setVisibility(1);
                }

            }
        }

        return $product;

    }

    public function setWebsiteIdsForProduct($productData, $product, $productIsNew = false)
    {
        $magentoWebsiteIds = $this->getMagentoWebsiteIds();
        if (isset($productData['shophub_additional_attributes']['website_ids'])) {
            $websiteIds = $productData['shophub_additional_attributes']['website_ids'];
            $websiteIdsToSet = array();

            if (is_array($websiteIds)) {
                foreach ($websiteIds as $key => $websiteId) {
                    if (in_array($websiteId, $magentoWebsiteIds)) {
                        $websiteIdsToSet[] = $websiteId;
                    }
                }
            } elseif (is_numeric($websiteIds)) {
                $websiteIdsToSet[] = $websiteIds;
            }
        } elseif ($productIsNew) {
            $websiteIdsToSet = $magentoWebsiteIds;
        }
        if (isset($websiteIdsToSet)) {
            $product->setWebsiteIds($websiteIdsToSet);
        }
        return $product;
    }

    protected function getMagentoWebsiteIds()
    {
        $websiteIds = [];
        foreach (Mage::app()->getWebsites() as $website) {
            $websiteIds[] = $website->getId();
        }
        return $websiteIds;
    }

}