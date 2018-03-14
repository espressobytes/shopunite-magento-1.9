<?php

class Shophub_ShopHubConnector_Helper_Connector_ExternalOrderInfo extends Shophub_ShopHubConnector_Helper_Data
{

    /** @var Shophub_ShopHubConnector_Helper_ApiClient */
    protected $apiClient;

    /** @var array */
    protected $importedOrderData = array();

    /**
     * Shophub_ShopHubConnector_Helper_Connector_OrderExport constructor.
     */
    public function __construct()
    {
        $this->apiClient = Mage::helper('shophubconnector/apiClient');
    }

    /**
     * @param $orderId
     * @return null
     */
    protected function importedOrderData($orderId)
    {
        $response = $this->apiClient->request('GET', "/orders?source_order_id=$orderId", []);
        if (isset($response['entries']))
            foreach ($response['entries'] as $orderData) {
                if (!isset($orderData['source_order_id'])) continue;
                if ($orderData['source_order_id'] != $orderId) continue;
                return $orderData;
            }
        return null;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return mixed
     */
    protected function getImportedDataForOrder($order)
    {
        $orderId = $order->getId();
        if (!isset($this->importedOrderData[$orderId])) {
            $orderData = $this->importedOrderData($orderId);
            if (!$orderData) {
                return null;
            }
            $this->importedOrderData[$orderId] = $orderData;
        }
        return $this->importedOrderData[$orderId];
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return int
     */
    public function getOrderStatus($order)
    {
        $importedData = $this->getImportedDataForOrder($order);
        if (isset($importedData['target_order_status'])) {
            return $importedData['target_order_status'];
        }
        return null;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return string
     */
    public function getOrderStatusLabel($order)
    {
        $importedData = $this->getImportedDataForOrder($order);
        if (isset($importedData['target_order_status_label'])) {
            return $importedData['target_order_status_label'];
        }
        // if no order can be found in external system return Magento-Status:
        return $order->getStatusLabel();
    }

    /**
     * @param $order
     * @return string
     */
    public function getPackageNumbers($order)
    {
        $importedData = $this->getImportedDataForOrder($order);
        if (!isset($importedData['order_package_numbers'])) {
            return null;
        }
        if (!$importedData['order_package_numbers']) {
            return null;
        }
        return implode(", ",$importedData['order_package_numbers']);
    }

    /**
     * @param $blockObj
     * @throws Exception
     */
    public function replaceOrderCollection($blockObj)
    {
        $orders = $blockObj->getOrders();

        $orderCollection = new Varien_Data_Collection();
        foreach ($orders as $order) {
            $orderObj = $this->getOrderObjectWithExternalInfos($order);
            $orderCollection->addItem($orderObj);
        }
        $blockObj->setOrders($orderCollection);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return Shophub_ShopHubConnector_Model_Sales_OrderFrontend
     */
    public function getOrderObjectWithExternalInfos($order)
    {
        $orderData = $order->getData();
        $orderData['external_status_label'] = $this->getOrderStatusLabel($order);
        $orderData['package_numbers'] = $this->getPackageNumbers($order);
        $orderObj = new Shophub_ShopHubConnector_Model_Sales_OrderFrontend();
        $orderObj->setData($orderData);
        return $orderObj;
    }

}