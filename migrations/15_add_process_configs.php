<?php


class AddProcessConfigs extends Migration {

    function up()
    {
        DBManager::get()->exec("
            CREATE TABLE `fleximport_process_configs` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `name` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `process_id` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `value` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `chdate` int(11) DEFAULT NULL,
                `mkdate` int(11) DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `config_name_2` (`name`,`process_id`),
                KEY `config_name` (`name`),
                KEY `process_id` (`process_id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }

    function down()
    {
        DBManager::get()->exec("
            DROP TABLE `fleximport_process_configs`
        ");
    }
}
