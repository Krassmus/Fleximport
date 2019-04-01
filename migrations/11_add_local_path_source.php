<?php


class AddLocalPathSource extends Migration {

    function up()
    {
        DBManager::get()->exec("
            ALTER TABLE `fleximport_tables` CHANGE `source` 
                `source` enum('csv_upload','csv_weblink','csv_studipfile','database','extern','sqlview','csv_path') NOT NULL DEFAULT 'csv_upload';
        ");
        SimpleORMap::expireTableScheme();
    }

    function down()
    {
        DBManager::get()->exec("
            ALTER TABLE `fleximport_tables` CHANGE `source` 
                `source` enum('csv_upload','csv_weblink','csv_studipfile','database','extern','sqlview') NOT NULL DEFAULT 'csv_upload';
        ");
        SimpleORMap::expireTableScheme();
    }
}