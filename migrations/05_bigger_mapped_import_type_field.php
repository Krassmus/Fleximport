<?php

class BiggerMappedImportTypeField extends Migration {
    function up()
    {
        DBManager::get()->exec("
	        ALTER TABLE `fleximport_mapped_items`
            CHANGE `import_type` `import_type` varchar(128) NOT NULL AFTER `mapping_id`;
	    ");
    }
    function down()
    {
        DBManager::get()->exec("
	        ALTER TABLE `fleximport_mapped_items`
            CHANGE `import_type` `import_type` varchar(64) NOT NULL AFTER `mapping_id`;
	    ");
    }
}