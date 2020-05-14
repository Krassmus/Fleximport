<?php


class AddForeignKeys extends Migration {

    function up()
    {
        DBManager::get()->exec("
            CREATE TABLE `fleximport_foreign_keys` (
                `key_id` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT '',
                `item_id` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin DEFAULT NULL,
                `sormclass` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
                `foreign_key` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
                `mkdate` int(11) DEFAULT NULL,
                PRIMARY KEY (`key_id`),
                UNIQUE KEY `item_id_2` (`item_id`,`sormclass`),
                UNIQUE KEY `foreign_key_2` (`foreign_key`,`sormclass`),
                KEY `item_id` (`item_id`),
                KEY `sormclass` (`sormclass`),
                KEY `foreign_key` (`foreign_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }

    function down()
    {
        DBManager::get()->exec("
	        DROP TABLE `fleximport_foreign_keys`
	    ");
    }
}
