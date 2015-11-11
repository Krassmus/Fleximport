<?php

class SaveMappedData extends Migration {

    function up()
    {
        DBManager::get()->exec("
	        CREATE TABLE IF NOT EXISTS `fleximport_mapped_items` (
                `mapping_id` varchar(32) NOT NULL,
                `import_type` varchar(64) NOT NULL,
                `item_id` varchar(100) NOT NULL,
                `mkdate` bigint(20) NOT NULL,
                `chdate` bigint(20) NOT NULL,
                PRIMARY KEY (`mapping_id`),
                KEY `import_type` (`import_type`),
                KEY `item_id` (`item_id`)
            )
	    ");
        DBManager::get()->exec("
            ALTER TABLE `fleximport_tables` ADD `synchronization` TINYINT NOT NULL DEFAULT '0' AFTER `display_lines`;
        ");
    }

    function down()
    {
        DBManager::get()->exec("
	        DROP TABLE IF EXISTS `fleximport_mapped_items`;
	    ");
    }
}