<?php


class AddTablePush extends Migration {

    function up()
    {
        DBManager::get()->exec("
            ALTER TABLE `fleximport_tables` 
            ADD `pushupdate` tinyint(4) DEFAULT '0' AFTER `webhook_urls`;
        ");
        SimpleORMap::expireTableScheme();
    }

    function down()
    {
        DBManager::get()->exec("
	        ALTER TABLE `fleximport_tables`
	        DROP `pushupdate`
	    ");
        SimpleORMap::expireTableScheme();
    }
}