<?php

class Shophub_ShopHubAddons_Helper_OrderExport extends Shophub_ShopHubConnector_Helper_Connector_OrderExport
{

    /**
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function getAdditionalData($order)
    {
        $customerReference = $this->getCustomerReferenceFromOrder($order);
        if ($customerReference) {
            $plentyProperties = array(
                array(
                    "typeId" => 8,
                    "value" => $customerReference
                )
            );
            $additionalData = array(
                "plenty_order" => array(
                    "properties" => $plentyProperties
                )
            );
            return $additionalData;
        }
        return array();
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return null
     */
    protected function getCustomerReferenceFromOrder($order)
    {
        try {
            $method = $order->getPayment()->getMethod();
            if ($method == 'ratepay_rechnung' || $method == 'ratepay_rate' || $method == 'ratepay_directdebit') {
                $additionalInformation = $order->getPayment()->getAdditionalInformation();
                if ((!$additionalInformation) || (!is_array($additionalInformation))) return null;
                if (isset($additionalInformation['descriptor'])) {
                    return $additionalInformation['descriptor'];
                }
            }
        } catch (Exception $e) {
            // Fallback
        }
        return null;
    }

    /**
     * @param $parameters
     * @param Mage_Sales_Model_Order $order
     * @return mixed
     */
    protected function addAdditionalDataToRequestParameters($parameters, $order)
    {
        $parameters = parent::addAdditionalDataToRequestParameters($parameters, $order);
        if (isset($parameters['order']['transaction_id'])) {
            return $parameters;
        }
        try {
            $transactionId = $this->getPaymentTransactionId($order, $order->getPayment()->getMethod());
            if (!$transactionId) {
                return $parameters;
            }
            $parameters['order']['transaction_id'] = $transactionId;
        } catch (Exception $e) {
            // Fallback
        }
        return $parameters;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param $method
     * @return null
     */
    protected function getPaymentTransactionId($order, $method)
    {
        if ($method == 'paymentnetwork_pnsofortueberweisung') {
            $additionalInformation = $order->getPayment()->getAdditionalInformation();
            if ((!$additionalInformation) || (!is_array($additionalInformation))) {
                return null;
            }
            if (isset($additionalInformation['sofort_transaction_id'])) {
                return $additionalInformation['sofort_transaction_id'];
            }
        }
        return null;
    }

}