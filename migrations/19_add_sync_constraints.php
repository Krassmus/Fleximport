<?php

class AddSyncConstraints extends Migration {

    function up()
    {
        DBManager::get()->exec("
            ALTER TABLE `fleximport_tables`
            ADD `sync_constraints` TEXT DEFAULT '' AFTER `synchronization`;
        ");
        SimpleORMap::expireTableScheme();
    }

    function down()
    {
        DBManager::get()->exec("
	        ALTER TABLE `fleximport_tables`
	        DROP `sync_constraints`
	    ");
        SimpleORMap::expireTableScheme();
    }
}
