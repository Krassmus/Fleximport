<?php


class InitPlugin extends Migration {
    
	public function up() {
	    DBManager::get()->exec("
	        CREATE TABLE IF NOT EXISTS `fleximport_tables` (
                `table_id` varchar(32) NOT NULL,
                `name` varchar(64) NOT NULL,
                `tabledata` text NULL,
                `csv_upload` tinyint(4) NOT NULL DEFAULT '1',
                `position` INT NOT NULL DEFAULT '1',
                `display_lines` ENUM('all','onlybroken','ondemand') NOT NULL DEFAULT 'all',
                `chdate` bigint(20) NOT NULL,
                `mkdate` bigint(20) NOT NULL
            ) ENGINE=InnoDB
	    ");
	}
	
	public function down() {
        $statement = DBManager::get()->prepare("
            SELECT name FROM `fleximport_tables`
        ");
        $statement->execute();
        foreach ($statement->fetch(PDO::FETCH_COLUMN, 0) as $table_name) {
            DBManager::get()->exec("
                DROP TABLE IF EXISTS `".add_slashes($table_name)."`;
            ");
        }
        DBManager::get()->exec("
	        DROP TABLE IF EXISTS `fleximport_tables`;
	    ");
	}
}