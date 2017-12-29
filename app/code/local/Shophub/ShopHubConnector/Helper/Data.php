<?php

class Shophub_ShopHubConnector_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * @param $shopHubConnectorPath
     * @return mixed
     */
    public function getConfigValue($shopHubConnectorPath)
    {
        return Mage::getStoreConfig('shophubconnector_settings/' . $shopHubConnectorPath);
    }

    /**
     * get DateTime for saving to Database (if no timestamp is given as parameter, then the current one is taken)
     * @param null $timeStamp
     * @return bool|string
     */
    public function getDateTime($timeStamp = null) {
        if (!$timeStamp) {
            $timeStamp = $this->getCurrentTimeStamp();
        }
        return date("Y-m-d H:i:s", $timeStamp);
    }

    /**
     * @return integer
     */
    public function getCurrentTimeStamp() {
        return Mage::getModel('core/date')->timestamp(time());
    }

    /**
     * @param $e
     * @param bool $msg
     */
    public function logException($e, $msg = false) {
        // TODO: build log table and log exception!
    }
}