<?php


class AddTableCaching extends Migration {

    function up()
    {
        DBManager::get()->exec("
	        ALTER TABLE `fleximport_processes`
	        ADD `cache_tables` int(11) NOT NULL DEFAULT '0' AFTER `webhookable`,
            ADD `last_data_import` int(11) DEFAULT NULL AFTER `cache_tables`
        ");
        SimpleORMap::expireTableScheme();
    }

    function down()
    {
        DBManager::get()->exec("
	        ALTER TABLE `fleximport_processes`
	        DROP `cache_tables`,
            DROP `last_data_import`
	    ");
        SimpleORMap::expireTableScheme();
    }
}