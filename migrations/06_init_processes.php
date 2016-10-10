<?php

require_once __DIR__."/../classes/FleximportProcess.php";

class InitProcesses extends Migration {

    function up()
    {
        DBManager::get()->exec("
	        CREATE TABLE IF NOT EXISTS `fleximport_processes` (
                `process_id` varchar(32) NOT NULL DEFAULT '',
                `name` varchar(100) NOT NULL DEFAULT '',
                `description` TEXT NULL,
                `triggered_by_cronjob` tinyint(11) DEFAULT NULL,
                `chdate` int(11) NOT NULL,
                `mkdate` int(11) NOT NULL,
                PRIMARY KEY (`process_id`)
            );
	    ");
        DBManager::get()->exec("
            ALTER TABLE `fleximport_tables` ADD `process_id` VARCHAR(32) NULL AFTER `table_id`;
        ");

        $statement = DBManager::get()->prepare("
            SELECT COUNT(*) as number
            FROM fleximport_tables
        ");
        $statement->execute();
        if ($statement->fetch(PDO::FETCH_COLUMN, 0) > 0) {
            $process = new FleximportProcess();
            $process['name'] = "Import";
            $process['triggered_by_cronjob'] = 1;
            $process->store();

            $statement = DBManager::get()->prepare("
                UPDATE `fleximport_tables`
                SET process_id = :process_id
            ");
            $statement->execute(array(
                'process_id' => $process->getId()
            ));
        }

        DBManager::get()->exec("
            ALTER TABLE `fleximport_tables` CHANGE `source` 
                `source` enum('csv_upload','csv_weblink','csv_studipfile','database','extern','sqlview') NOT NULL DEFAULT 'csv_upload';
        ");

    }

    function down()
    {
        DBManager::get()->exec("
	        DROP TABLE IF EXISTS `fleximport_processes`;
	    ");
    }
}