<?php

class Shophub_ShopHubConnector_OrdergridController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Controller for exporting orders via order grid mass action
     * http://www.magentoroot.com/shophubconnector/ordergrid/export/key/xyz/
     */
    public function exportOrdersAction()
    {
        $request = $this->getRequest();
        $orderIds = $request->getParam('order_ids');

        if ($orderIds) {
            if (sizeof($orderIds) > 0) {
                $this->exportOrderIds($orderIds);
            }
        }

        $this->_redirectReferer();

    }

    /**
     * @param $orderIds array
     */
    protected function exportOrderIds($orderIds)
    {
        /** @var $orderExportHelper Shophub_ShopHubConnector_Helper_Connector_OrderExport */
        $orderExportHelper = Mage::helper('shophubconnector/connector_orderExport');

        $exportedOrders = array();
        $exportedOrdersFailures = array();
        $failureMessages = array();
        $successMessages = array();

        foreach ($orderIds as $orderId) {
            $order = Mage::getModel('sales/order')->load($orderId);
            $exportSuccess = $orderExportHelper->exportOrderToShopHub($orderId);
            if ($exportSuccess) {
                $exportedOrders[] = $order->getIncrementId();
                $successMessages[] = $this->buildSuccessMessageFromResponse($order, $orderExportHelper->getLastResponse());
            } else {
                $exportedOrdersFailures[] = $order->getIncrementId();
                $failureMessages[] = $order->getIncrementId() . ' (Response: ' . $orderExportHelper->getLastStatusCode() . ' ' . $orderExportHelper->getLastResponse() . ') ';
            }
        }

        $exportedCount = sizeof($exportedOrders);
        if ($exportedCount > 0) {
            $strOrder = $exportedCount == 1 ? "order" : "orders";
            $successMessage = "$exportedCount $strOrder successfully exported: " . implode(", ", $exportedOrders) . '; <br>Details:<br>' . implode("<br>", $successMessages);
            Mage::getSingleton('core/session')->addSuccess($successMessage);
        }

        $failuresCount = sizeof($exportedOrdersFailures);
        if ($failuresCount > 0) {
            $strOrder = $failuresCount == 1 ? "order" : "orders";
            $errorMessage = "$failuresCount $strOrder could not be exported: " . implode(", ", $exportedOrdersFailures) . '; <br>Details:<br>' . implode("<br>", $failureMessages);
            Mage::getSingleton('core/session')->addError($errorMessage);
        }

    }

    private function buildSuccessMessageFromResponse($order, $response) {
        try {
            $responseData = json_decode($response, true);
            $msg = 'Order: ' . $order->getIncrementId() . ' - shophub-id ' . $responseData['order_id'];
            if (isset($responseData['target_order_id'])) {
                $msg .= ' - ext-id: ' . $responseData['target_order_id'];
            }
            return $msg;
        } catch (\Exception $e) {
            return "";
        }
    }

}








