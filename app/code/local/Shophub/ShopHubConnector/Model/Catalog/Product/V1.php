<?php

class Shophub_ShopHubConnector_Model_Catalog_Product_V1 extends Mage_Catalog_Model_Api2_Product_Rest_Admin_V1
{

    /** @var Shophub_ShopHubConnector_Helper_Data */
    protected $shophubConnectorHelper;

    /** @var Shophub_ShopHubConnector_Helper_Catalog_products */
    protected $productHelper;

    /** @var Shophub_ShopHubConnector_Helper_Catalog_Products_ProductDataConnector */
    protected $productDataConnector;

    /** @var Shophub_ShopHubConnector_Helper_Catalog_ConfigurableProducts */
    protected $configurableProductHelper;

    /** @var Shophub_ShopHubConnector_Helper_Catalog_Products_ImageConnector */
    protected $imageConnector;

    /** @var array */
    protected $configProductSetting;

    /** @var array */
    protected $productImages = array();

    public function __construct()
    {
        $this->shophubConnectorHelper = Mage::helper('shophubconnector');
        $this->productHelper = Mage::helper('shophubconnector/catalog_products');
        $this->productDataConnector = Mage::helper('shophubconnector/catalog_products_productDataConnector');
        $this->imageConnector = Mage::helper('shophubconnector/catalog_products_imageConnector');
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
        Mage::app()->setCurrentStore(Mage::getModel('core/store')->load(Mage_Core_Model_App::ADMIN_STORE_ID));

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
            $this->importImagesForSimpleProduct($result);
            if (isset($this->configProductSetting['parent_product_sku'])) {
                $connectionResult = $this->productHelper->connectSimpleToConfigProductByProductData($data, $this->configProductSetting);
            }
            return $result;
        }

        // if product type_id is configurable, use own logic to create product.
        $this->configurableProductHelper = Mage::helper('shophubconnector/catalog_configurableProducts');

        try {
            $product = $this->productHelper->createOrUpdateConfigurableProduct($data, $this->configProductSetting, $this->productImages);
            if (!$product) {
                $errorMessage = 'Error: Configurable product could not be created! Function createOrUpdateConfigurableProduct(...) returned wrong result.';
                $this->shophubConnectorHelper->log($errorMessage);
                $this->_critical($errorMessage, Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR);
            }
            return $this->_getLocation($product);
        } catch (Exception $e) {
            $this->shophubConnectorHelper->logException($e);
            $exceptionMessage = $e->getMessage();
            $exceptionTrace = $e->getTraceAsString();
            throw new Mage_Api2_Exception("Configurable product could not be created! Exception occurred in function createOrUpdateConfigurableProduct(...). Exception-Message: $exceptionMessage --- Trace: $exceptionTrace", 400);
        }
    }

    /**
     * @param $result
     */
    protected function importImagesForSimpleProduct($result)
    {
        $this->shophubConnectorHelper->log('Import images for simple product ...');
        if (sizeof($this->productImages) == 0) {
            $this->shophubConnectorHelper->log('Info: No images in request. Do not import any images.');
            return;
        }
        if (!$result) {
            $this->shophubConnectorHelper->log('Warning: Cannot import images: result negative!');
            return;
        }
        if (!isset($this->_product)) {
            $productId = $this->getProductIdFromLocation($result);
            $product = Mage::getModel('catalog/product')->load($productId);
        } elseif (!$this->_product->getId()) {
            $productId = $this->getProductIdFromLocation($result);
            $product = Mage::getModel('catalog/product')->load($productId);
        } else {
            $product = $this->_product;
        }
        if (!$product) {
            $this->shophubConnectorHelper->log('Error: Could not find any product to import images!');
            return;
        }
        try {
            $importImagesResult = $this->imageConnector->importImages($product, $this->productImages);
            if ($importImagesResult) {
                $this->shophubConnectorHelper->log('import images successful');
            } else {
                $this->shophubConnectorHelper->log('import images failed. Reason unknown');
            }
        } catch (Exception $e) {
            $this->shophubConnectorHelper->log('import images failed! Exception: ');
            $this->shophubConnectorHelper->logException($e);
        }
    }

    /**
     * @param $locationStr
     * @return null
     */
    protected function getProductIdFromLocation($locationStr)
    {
        if (!is_string($locationStr)) {
            return null;
        }
        preg_match("/api\/rest\/products\/(.*)/", $locationStr, $match);
        if (isset($match[1])) {
            return $match[1];
        }
        return null;
    }

    /**
     * @param $data
     * @return string
     * @throws Exception
     */
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

    /**
     * @param $data
     * @return string
     */
    protected function retrieveShophubMethod($data)
    {
        if (isset($data['shophub_method'])) {
            return $data['shophub_method'];
        }
        return 'create_or_update';
    }

    /**
     * @param $data
     * @return mixed
     */
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

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param array $productData
     */
    protected function _prepareDataForSave($product, $productData)
    {
        $product->setVisibility(1);
        $product->setCreatedAt(strtotime('now'));

        $this->productDataConnector->setWebsiteIdsForProduct($productData, $product);

        parent::_prepareDataForSave($product, $productData);
    }

}