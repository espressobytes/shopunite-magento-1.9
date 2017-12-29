<?php

$installer = $this;
$installer->startSetup();

// generate token table
$installer->run("
		-- DROP TABLE IF EXISTS {$this->getTable('shophubconnector/accessTokenTable')};
		CREATE TABLE IF NOT EXISTS {$this->getTable('shophubconnector/accessTokenTable')} (
		`id` int(11) unsigned NOT NULL auto_increment,
		`token` text NOT NULL default '',
		`api_username` varchar(255) NOT NULL default '',
		`created_at` datetime NULL,
		`valid_until` datetime NULL,
		PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

// generate api log table
$installer->run("
		-- DROP TABLE IF EXISTS {$this->getTable('shophubconnector/apiLog')};
		CREATE TABLE IF NOT EXISTS {$this->getTable('shophubconnector/apiLog')} (
		`id` int(11) unsigned NOT NULL auto_increment,
		`date_time` datetime NULL,
		`method` varchar(16) NOT NULL default '',
		`route` varchar(16) NOT NULL default '',
		`parameters` text default '',
		`response_status_code` int NULL,
		`response_content` text default '',
		`error_message` varchar(255) default '',
		PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();








