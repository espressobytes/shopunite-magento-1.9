<?php

$installer = $this;
$installer->startSetup();

try {
    $installer->run("
        ALTER TABLE {$this->getTable('shophubconnector/apiLog')}
        MODIFY `error_message` TEXT
        ;");
} catch ( Exception $e ) {
    Mage::helper('shophubconnector')->logException($e);
}

$installer->endSetup();



