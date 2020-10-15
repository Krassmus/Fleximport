<?php


class MakeTablesInactive extends Migration {

    function up()
    {
        DBManager::get()->exec("
            ALTER TABLE `fleximport_tables`
            ADD COLUMN `active` tinyint(1) DEFAULT 1 AFTER `pushupdate`
        ");
        SimpleORMap::expireTableScheme();
    }

    function down()
    {
        DBManager::get()->exec("
            ALTER TABLE `fleximport_tables`
            DROP COLUMN `active`;
        ");
        SimpleORMap::expireTableScheme();

    }
}
