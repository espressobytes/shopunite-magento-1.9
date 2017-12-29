<?php

class Shophub_ShopHubConnector_Model_ApiLog extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('shophubconnector/apiLog');
    }

    public function save()
    {
        parent::save();
        $this->deleteOldEntries();
    }

    /**
     * to reduce database memory delete old api-log data
     */
    private function deleteOldEntries()
    {
        $saveLogEntriesSize = Mage::helper('shophubconnector/data')->getConfigValue('dev/apilog_max_size');
        if (!is_numeric($saveLogEntriesSize)) {
            return;
        }
        $apiLogTotalSize = $this->getCollection()->getSize();
        if ($apiLogTotalSize < $saveLogEntriesSize) {
            return;
        }

        $currentId = $this->getId();
        $deleteLogEntriesBeforeId = $currentId - $saveLogEntriesSize + 1;
        $apiLogCollection = $this->getCollection()
            ->addFieldToFilter('id', array('lt' => $deleteLogEntriesBeforeId));

        foreach ($apiLogCollection as $apiLogItem) {
            $apiLogItem->delete();
        }
    }


}
