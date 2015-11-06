<?php

class SetupConfigs extends Migration {

    function up()
    {
        DBManager::get()->exec("
	        CREATE TABLE IF NOT EXISTS `fleximport_configs` (
                `name` varchar(64) NOT NULL,
                `value` text NOT NULL,
                `mkdate` bigint(20) NOT NULL,
                `chdate` bigint(20) NOT NULL,
                PRIMARY KEY (`name`)
            )
	    ");
    }

    function down()
    {
        DBManager::get()->exec("
	        DROP TABLE IF EXISTS `fleximport_configs`;
	    ");
    }
}