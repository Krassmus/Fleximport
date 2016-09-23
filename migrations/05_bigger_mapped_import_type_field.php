<?php

class BiggerMappedImportTypeField extends Migration {

    function up()
    {
        DBManager::get()->exec("
	        CREATE TABLE IF NOT EXISTS `fleximport_processes` (
                `process_id` varchar(32) NOT NULL DEFAULT '',
                `name` varchar(100) NOT NULL DEFAULT '',
                `triggered_by_cronjob` tinyint(11) DEFAULT NULL,
                `chdate` int(11) NOT NULL,
                `mkdate` int(11) NOT NULL,
                PRIMARY KEY (`process_id`)
            );
	    ");
        DBManager::get()->exec("
            ALTER TABLE `fleximport_tables` ADD `process_id` VARCHAR(32) NULL AFTER `table_id`;
        ");
    }

    function down()
    {
        DBManager::get()->exec("
	        DROP TABLE IF EXISTS `fleximport_processes`;
	    ");
    }
}