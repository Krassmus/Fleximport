<?php
/* 
 *  Copyright (c) 2011  Rasmus Fuhse <fuhse@data-quest.de>
 * 
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

require_once dirname(__file__)."/../classes/NSI_PersonenTable.class.php";

class NSI_PersonenTableTest extends UnitTestCase {
    protected $table_name = "person";

    function setUp() {
        $db = DBManager::get();
        $db->exec(
            "TRUNCATE TABLE `auth_user_md5` " .
        "");
        $db->exec(
            "TRUNCATE TABLE `user_info` " .
        "");

        $db->exec(
            "CREATE TABLE IF NOT EXISTS `person` (
              `IMPORT_TABLE_PRIMARY_KEY` bigint(20) NOT NULL AUTO_INCREMENT,
              `safo_key` text NOT NULL,
              `geschlecht` text NOT NULL,
              `titel` text NOT NULL,
              `vorname` text NOT NULL,
              `nachname` text NOT NULL,
              `email` text NOT NULL,
              `status` text NOT NULL,
              `satzart` text NOT NULL,
              `stand` text NOT NULL,
              PRIMARY KEY (`IMPORT_TABLE_PRIMARY_KEY`)
            ) ENGINE=MyISAM" .
        "");
        $db->exec(
            "INSERT INTO `".addslashes($this->table_name)."` " .
            "SET safo_key = '001', " .
                "geschlecht = 'M', " .
                "titel = '', " .
                "vorname = 'Rasmus', " .
                "nachname = 'Fuhse', " .
                "email = 'fuhse@data-quest.de', " .
                "status = 'autor', " .
                "satzart = '', " .
                "stand = '2011-06-01 14:13:00' " .
        "");
        $db->exec(
            "INSERT INTO `".addslashes($this->table_name)."` " .
            "SET safo_key = '002', " .
                "geschlecht = 'M', " .
                "titel = '', " .
                "vorname = 'Rasmus', " .
                "nachname = 'Fuhse', " .
                "email = 'ras@fuhse.org', " .
                "status = 'autor', " .
                "satzart = '', " .
                "stand = '2011-06-01 14:13:00' " .
        "");
        $db->exec(
            "INSERT IGNORE INTO datafields " .
            "SET datafield_id = MD5(".$db->quote($GLOBALS['safo_datafield'])."), " .
                "name = ".$db->quote($GLOBALS['safo_datafield']).", " .
                "object_type = 'user', " .
                "type = 'textline' " .
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

    function test_createUser() {
        $db = DBManager::get();

        $count_user = $db->query("SELECT COUNT(*) FROM auth_user_md5 ")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual("0", $count_user);

        $table = new NSI_PersonenTable($this->table_name);
        $table->process();

        $count_user = $db->query("SELECT COUNT(*) FROM auth_user_md5 ")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual("2", $count_user);

        $secon_user_with_same_username = $db->query(
            "SELECT username FROM auth_user_md5 WHERE Email = 'ras@fuhse.org' " .
        "")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual("rasmus.fuhse2", $secon_user_with_same_username);
    }

    function test_changeUser() {
        $db = DBManager::get();

        $olduser_username = "";
        $db->exec(
            "INSERT IGNORE INTO auth_user_md5 " .
            "SET username = 'rasmus.fuhse', " .
                "user_id = '".md5('rasmus.fuhse')."', " .
                "Vorname = 'Ras', " .
                "Nachname = 'Fuhse', " .
                "perms = 'tutor' " .
        "");
        $db->exec(
            "INSERT INTO datafields_entries " .
            "SET datafield_id = MD5(".$db->quote($GLOBALS['safo_datafield'])."), " .
                "range_id = '".md5('rasmus.fuhse')."', " .
                "content = '001', " .
                "mkdate = ".$db->quote(time()-60).", " .
                "chdate = ".$db->quote(time()-60)." " .
        "");

        $table = new NSI_PersonenTable($this->table_name);
        $table->process();

        $rasmus = $db->query(
            "SELECT * FROM auth_user_md5 WHERE username = 'rasmus.fuhse' " .
        "")->fetch(PDO::FETCH_ASSOC);
        $this->assertEqual($rasmus['Vorname'], "Rasmus");
    }

}