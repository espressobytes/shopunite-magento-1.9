<?php

class Shophub_ShopHubConnector_Model_Catalog_Product_V1 extends Mage_Catalog_Model_Api2_Product_Rest_Admin_V1
{

    /** @var Shophub_ShopHubConnector_Helper_Catalog_products */
    protected $productHelper;

    /** @var Shophub_ShopHubConnector_Helper_Catalog_Products_ProductDataConnector */
    protected $productDataConnector;

    protected $configProductSetting;

    protected $productImages = array();

    public function __construct()
    {
        $this->productHelper = Mage::helper('shophubconnector/catalog_products');
        $this->productDataConnector = Mage::helper('shophubconnector/catalog_products_productDataConnector');
        $this->configProductSetting = null;
    }

    /**
     * Define here all the additional data, that can be included in the requestData-Array
     * @param string $userType
     * @param string $operation
     * @return array
     */
    public function getAvailableAttributes($userType, $operation)
    {
        $origAttributes = parent::getAvailableAttributes($userType, $operation);
        $origAttributes['shophub_product_configuration'] = 'Shophub product configuration';
        $origAttributes['shophub_product_images'] = 'Shophub product images';
        $origAttributes['shophub_additional_attributes'] = 'Shophub additional attributes';
        $origAttributes['shophub_method'] = 'Shophub method';
        return $origAttributes;
    }

    /**
     * @param array $data
     * @return string
     */
    public function create(array $data)
    {
        return $this->_create($data);
    }

    /**
     * @param array $data
     * @return string
     * @throws Exception
     * @throws Mage_Api2_Exception
     */
    public function _create(array $data)
    {
        $dataShophub = $data;

        Mage::app()->setCurrentStore(Mage::getModel('core/store')->load(Mage_Core_Model_App::ADMIN_STORE_ID));

        /* @var $validator Mage_Catalog_Model_Api2_Product_Validator_Product */

        /*
        $validator = Mage::getModel('catalog/api2_product_validator_product', array(
            'operation' => self::OPERATION_CREATE
        ));
        */

        /* @var $validator Shophub_ShopHubConnector_Model_Catalog_Product_Validator */
        $validator = Mage::getModel('shophubconnector/catalog_product_validator', array(
            'operation' => self::OPERATION_CREATE
        ));

        $data = $this->retrieveShopHubProductData($data);
        $method = $this->retrieveShophubMethod($data);
        if ($method == 'update') {
            return $this->updateProduct($data);
        }

        if (!$validator->isValidData($data)) {
            foreach ($validator->getErrors() as $error) {
                $this->_error($error, Mage_Api2_Model_Server::HTTP_BAD_REQUEST);
            }
            $this->_critical(self::RESOURCE_DATA_PRE_VALIDATION_ERROR);
        }

        $data = $validator->getModifiedData($data);

        // if product type_id is simple, create simple product with Magento-Procedure. Then use own logic, to create relationship between configurable and simple product
        $type = $data['type_id'];
        if ($type !== 'configurable') {
            $this->productHelper->addConfigAttrValuesToProductData($data, $this->configProductSetting);
            $productId = Mage::getModel('catalog/product')->getIdBySku($data['sku']);
            if (!$productId) {
                if ($method == 'create_or_update') {
                    $result = parent::_create($data);
                } else {
                    $result = null;
                }
            } else {
                $this->_product = Mage::getModel('catalog/product')->load($productId);
                parent::_update($data);
                $result = $this->_getLocation($this->_product);
            }
            if (isset($this->configProductSetting['parent_product_sku'])) {
                $connectionResult = $this->productHelper->connectSimpleToConfigProductByProductData($data, $this->configProductSetting);
            }
            return $result;
        }

        // if product type_id is configurable, use own logic to create product.
        $this->configurableProductHelper = Mage::helper('shophubconnector/catalog_configurableProducts');
        $product = $this->productHelper->createOrUpdateConfigurableProduct($data, $this->configProductSetting, $this->productImages);
        if (!$product) {
            $this->_critical('Configurable product could not be created!', Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR);
        }
        return $this->_getLocation($product);

    }

    protected function updateProduct($data)
    {
        if (!isset($data['sku'])) {
            throw new Exception("Validation Error: No sku given");
        }
        $sku = $data['sku'];
        $productId = Mage::getModel('catalog/product')->getIdBySku($data['sku']);
        if (!$productId) {
            throw new Exception("Error: no product found with sku: $sku");
        }
        $this->_product = Mage::getModel('catalog/product')->load($productId);
        $updateResult = $this->productDataConnector->updateProductData($data, $this->_product);
        if ($updateResult) {
            return $this->_getLocation($this->_product);
        }
        throw new Exception($this->productDataConnector->getLastErrorMsg());
    }

    protected function retrieveShophubMethod($data)
    {
        if (isset($data['shophub_method'])) {
            return $data['shophub_method'];
        }
        return 'create_or_update';
    }

    protected function retrieveShopHubProductData($data)
    {
        if (isset($data['shophub_product_configuration'])) {
            $this->configProductSetting = $data['shophub_product_configuration'];
            unset($data['shophub_product_configuration']);
        }
        if (isset($data['shophub_product_images'])) {
            $this->productImages = $data['shophub_product_images'];
            unset($data['shophub_product_images']);
        }
        return $data;
    }

    protected function _prepareDataForSave($product, $productData)
    {
        $product->setVisibility(1);
        $product->setCreatedAt(strtotime('now'));

        $this->productDataConnector->setWebsiteIdsForProduct($productData, $product);

        parent::_prepareDataForSave($product, $productData);
    }

}