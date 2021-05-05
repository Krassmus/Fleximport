<?php


class AddDurations extends Migration
{

    function up()
    {
        DBManager::get()->exec("
            ALTER TABLE `fleximport_processes`
            DROP COLUMN `cache_tables`;
        ");
        DBManager::get()->exec("
	        ALTER TABLE `fleximport_tables`
	        ADD `last_fetch_duration` int(11) NOT NULL DEFAULT '-1' AFTER `active`,
            ADD `last_import_duration` int(11) NOT NULL DEFAULT '-1' AFTER `last_fetch_duration`
        ");
        SimpleORMap::expireTableScheme();
    }

    function down()
    {
        /*DBManager::get()->exec("
	        ALTER TABLE `fleximport_processes`
	        ADD `cache_tables` int(11) NOT NULL DEFAULT '0',
            DROP COLUMN `last_fetch_duration`,
            DROP COLUMN `last_import_duration`
        ");
        SimpleORMap::expireTableScheme();*/
    }
}
