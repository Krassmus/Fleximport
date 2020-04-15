<?php


class AddProcessCharges extends Migration {

    function up()
    {
        DBManager::get()->exec("
            ALTER TABLE `fleximport_processes`
            ADD `charge` varchar(64) DEFAULT '' AFTER `triggered_by_cronjob`;
        ");
        SimpleORMap::expireTableScheme();
    }

    function down()
    {
        DBManager::get()->exec("
	        ALTER TABLE `fleximport_processes`
	        DROP `charge`
	    ");
        SimpleORMap::expireTableScheme();
    }
}
