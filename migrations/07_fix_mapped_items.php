<?php


class FixMappedItems extends Migration {

    function up()
    {
        DBManager::get()->exec("
	        ALTER TABLE `fleximport_mapped_items`
	        CHANGE `import_type` `table_id` VARCHAR(200) NOT NULL DEFAULT '0'
        ");
        SimpleORMap::expireTableScheme();
    }

    function down()
    {
        DBManager::get()->exec("
	        ALTER TABLE `fleximport_mapped_items`
	        CHANGE `table_id` `import_type` VARCHAR(200) NOT NULL DEFAULT '0'
	    ");
        SimpleORMap::expireTableScheme();
    }
}