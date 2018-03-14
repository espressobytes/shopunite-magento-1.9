<?php

class Shophub_ShopHubAddons_Helper_ExternalOrderInfo extends Shophub_ShopHubConnector_Helper_Connector_ExternalOrderInfo
{

    /**
     * @param Mage_Sales_Model_Order $order
     * @return string
     */
    public function getOrderStatusLabel($order)
    {
        $externalStatus = parent::getOrderStatus($order);
        if (!$externalStatus) {
            return parent::getOrderStatusLabel($order);
        }

        // here you can define your
        $statusLabels = [
            "1.0" => "Unvollständige Daten",

            "1.1" => "Warten auf Zahlung",
            "1.2" => "Warten auf Zahlung",
            "2.0" => "Warten auf Zahlung",
            "3.0" => "Warten auf Zahlung",
            "3.1" => "Warten auf Zahlung",
            "3.2" => "Warten auf Zahlung",
            "3.3" => "Warten auf Zahlung",
            "3.4" => "Warten auf Zahlung",

            "4.0" => "In Versandvorbereitung",
            "5.0" => "Im Versand",
            "5.1" => "Im Versand",
            "6.0" => "Im Versand",

            "7.0" => "Warenausgang gebucht",
            "7.1" => "Warenausgang gebucht",

            "8.0" => "Storniert",
            "8.1" => "Storniert",
            "9.0" => "Retourniert",
            "11.0" => "Gutschrift erzeugt",
        ];

        $externalStatus = floatval($externalStatus);
        $externalStatusStr = number_format($externalStatus, 1);

        if (isset($statusLabels[$externalStatusStr])) {
            return $statusLabels[$externalStatusStr];
        }
        return parent::getOrderStatusLabel($order);
    }

}