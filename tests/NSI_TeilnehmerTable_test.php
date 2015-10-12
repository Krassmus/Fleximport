<?php
/* 
 *  Copyright (c) 2011  Rasmus Fuhse <fuhse@data-quest.de>
 * 
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

require_once dirname(__file__)."/../classes/NSI_TeilnehmerTable.class.php";

class NSI_TeilnehmerTableTest extends UnitTestCase {
    protected $table_name = "teilnehmerliste";

    function setUp() {
        $db = DBManager::get();

        $db->exec(
            "CREATE TABLE IF NOT EXISTS `teilnehmerliste` (
              `IMPORT_TABLE_PRIMARY_KEY` bigint(20) NOT NULL AUTO_INCREMENT,
              `v_nr` text NOT NULL,
              `safo_key` text NOT NULL,
              PRIMARY KEY (`IMPORT_TABLE_PRIMARY_KEY`)
            ) ENGINE=MyISAM " .
        "");
        $db->exec(
            "INSERT IGNORE INTO datafields " .
            "SET datafield_id = MD5(".$db->quote($GLOBALS['safo_datafield'])."), " .
                "name = ".$db->quote($GLOBALS['safo_datafield']).", " .
                "object_type = 'user', " .
                "type = 'textline' " .
        "");
        $db->exec(
            "INSERT INTO datafields_entries " .
            "SET datafield_id = MD5(".$db->quote($GLOBALS['safo_datafield'])."), " .
                "content = '001', " .
                "range_id = MD5('rasmus.fuhse') " .
        "");
        $db->exec(
            "INSERT INTO datafields_entries " .
            "SET datafield_id = MD5(".$db->quote($GLOBALS['safo_datafield'])."), " .
                "content = '002', " .
                "range_id = MD5('rasmus.fuhse2') " .
        "");
        $db->exec(
            "INSERT INTO seminare " .
            "SET Name = 'bla1', " .
                "Seminar_id = MD5('bla1'), " .
                "Veranstaltungsnummer = '123' " .
        "");
        $db->exec(
            "INSERT INTO seminare " .
            "SET Name = 'bla2', " .
                "Seminar_id = MD5('bla2'), " .
                "Veranstaltungsnummer = '234' " .
        "");

        $db->exec(
            "INSERT INTO `".addslashes($this->table_name)."` " .
            "SET v_nr = '123', " .
                "safo_key = '001' " .
        "");
    }

    function tearDown() {
        $db = DBManager::get();
        $db->exec(
            "TRUNCATE TABLE `auth_user_md5` " .
        "");
        $db->exec(
            "TRUNCATE TABLE `user_info` " .
        "");
        $db->exec(
            "TRUNCATE TABLE `datafields` " .
        "");
        $db->exec(
            "TRUNCATE TABLE `datafields_entries` " .
        "");
        $db->exec(
            "DROP TABLE `".addslashes($this->table_name)."` " .
        "");
    }

    function test_process() {
        $db = DBManager::get();

        $count_su = $db->query(
            "SELECT COUNT(*) FROM seminar_user " .
        "")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual("0", $count_su);

        $table = new NSI_TeilnehmerTable($this->table_name);
        $table->process();

        $count_su = $db->query(
            "SELECT COUNT(*) FROM seminar_user " .
        "")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual("1", $count_su);
        
    }

    

}