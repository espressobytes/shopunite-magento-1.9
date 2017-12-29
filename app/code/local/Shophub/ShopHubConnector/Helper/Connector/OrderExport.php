<?php

class Shophub_ShopHubConnector_Helper_Connector_OrderExport extends Shophub_ShopHubConnector_Helper_Data
{

    /** @var Shophub_ShopHubConnector_Helper_ApiClient */
    protected $apiClient;

    /**
     * Shophub_ShopHubConnector_Helper_Connector_OrderExport constructor.
     */
    public function __construct()
    {
        $this->apiClient = Mage::helper('shophubconnector/apiClient');
    }

    /**
     * @return int
     */
    public function getLastStatusCode()
    {
        return $this->apiClient->lastStatusCode;
    }

    /**
     * @return string
     */
    public function getLastResponse()
    {
        return $this->apiClient->lastResponseMsg;
    }

    /**
     * @param $order Mage_Sales_Model_Order|int
     * @return bool
     */
    public function exportOrderToShopHub($order)
    {
        if (is_numeric($order)) {
            $order = Mage::getModel('sales/order')->load($order);
        } else {
            $order = Mage::getModel('sales/order')->load($order->getId());
        }
        /** @var Mage_Sales_Model_Order|null $order */
        if (!$order) {
            return false;
        }
        return $this->exportLoadedOrderToShopHub($order);
    }

    /**
     * @param $order Mage_Sales_Model_Order | integer
     * @return bool
     */
    public function exportLoadedOrderToShopHub($order)
    {
        $parameters = $this->getRequestParameters($order);
        $requestSuccess = $this->apiClient->request('POST', '/orders', $parameters);
        if ($requestSuccess) {
            return true;
        }
        return false;
    }

    /**
     * @param Mage_Sales_Model_Order|int $order
     * @param $newStatus
     * @return bool
     */
    public function updateOrderToShopHub($order, $newStatus)
    {
        if (is_numeric($order)) {
            $order = Mage::getModel('sales/order')->load($order);
        } else {
            $order = Mage::getModel('sales/order')->load($order->getId());
        }
        /** @var Mage_Sales_Model_Order|null $order */
        if (!$order) {
            return false;
        }
        $parameters = $this->getBaseInformation($order);
        $parameters['order']['data'] = [
            'status' => $newStatus
        ];
        $parameters = $this->addAdditionalDataToRequestParameters($parameters, $order);
        $requestSuccess = $this->apiClient->request('PUT', '/order', $parameters);

        // if order does not exist yet, export it in a new call
        if (!$requestSuccess && $this->apiClient->lastStatusCode == "404") {
            return $this->exportLoadedOrderToShopHub($order);
        }
        if ($requestSuccess) {
            return true;
        }
        return false;
    }

    /**
     * @param $order Mage_Sales_Model_Order
     * @return array
     */
    protected function getRequestParameters($order)
    {
        $parameters = $this->getBaseInformation($order);

        $parameters['order']['data'] = $order->getData();
        $parameters['order']['billing_address'] = $order->getBillingAddress()->getData();
        $parameters['order']['delivery_address'] = $order->getShippingAddress()->getData();
        $parameters['order']['items'] = $this->getOrderItems($order);

        $payment = $order->getPayment();
        $parameters['order']['payment_method'] = $payment->getMethod();

        $parameters = $this->addAdditionalDataToRequestParameters($parameters, $order);
        return $parameters;
    }

    /**
     * @param $parameters
     * @param $order Mage_Sales_Model_Order
     * @return mixed
     */
    protected function addAdditionalDataToRequestParameters($parameters, $order)
    {
        $payment = $order->getPayment();
        // transmit transaction id, if available
        if ($payment->getLastTransId()) {
            $parameters['order']['transaction_id'] = $payment->getLastTransId();
        }
        // use the function getAdditionalData to customize your order
        try {
            $additionalData = $this->getAdditionalData($order);
            $parameters['order']['additional_data'] = $additionalData;
        } catch (Exception $e) {
            // TODO: log Exception
            $parameters['order']['additional_data'] = array();
        }
        return $parameters;
    }

    /**
     * to set additional Data to your order, extend this class and overwrite this function
     * @param $order
     * @return array
     */
    protected function getAdditionalData($order)
    {
        return array();
    }

    /**
     * @param $order Mage_Sales_Model_Order
     * @return array
     */
    protected function getBaseInformation($order)
    {
        $parameters = array(
            'source_order_id' => $order->getId(),
            'source_order_no' => $order->getIncrementId(),
            'system' => array(
                'name' => 'magento',
                'version' => Mage::getVersion()
            ),
            'source_ref_id' => $this->getConfigValue('general/interface_ref_id'),
            'source_profile' => $this->getConfigValue('order_export/order_import_profile')
        );
        return $parameters;
    }

    /**
     * @param $order Mage_Sales_Model_Order
     * @return array
     */
    protected function getOrderItems($order)
    {
        $orderItems = array();
        $itemsCollection = $order->getAllVisibleItems();
        foreach ($itemsCollection as $item) {
            $itemData = $item->getData();
            $itemData['product_data'] = $this->getItemProductData($item);
            $itemData['product_options_arr'] = $this->getProductOptionsArr($item);
            $orderItems[] = $itemData;
        }
        return $orderItems;
    }

    /**
     * @param Mage_Sales_Model_Order_Item $item
     * @return mixed
     */
    protected function getItemProductData(Mage_Sales_Model_Order_Item $item)
    {
        $sku = $item->getSku();
        $simpleProduct = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
        if ($simpleProduct) {
            $productData = $simpleProduct->getData();
            return $productData;
        }
        return null;
    }

    /**
     * @param Mage_Sales_Model_Order_Item $item
     * @return mixed|null
     */
    protected function getProductOptionsArr(Mage_Sales_Model_Order_Item $item)
    {
        try {
            $productOptions = $item->getProductOptions();
            if (!$productOptions) return null;

            if (is_string($productOptions)) {
                $arr = unserialize($productOptions);
                return $arr;
            }
            if (is_array($productOptions)) {
                return $productOptions;
            }
        } catch (Exception $e) {
            // TODO: log exception
        }
        return null;
    }

}