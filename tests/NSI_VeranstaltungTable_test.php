<?php
/*
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Rasmus Fuhse <fuhse@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP-Plugins
 * @since       2.1
 */

require_once dirname(__file__)."/../classes/NSI_VeranstaltungTable.class.php";

require_once 'lib/phplib/user4.inc';

class NSI_VeranstaltungTableTest extends UnitTestCase {

    protected $table_name = "veranstaltung";
    
    function setUp() {
        $db = DBManager::get();
        $db->exec("TRUNCATE TABLE sem_tree ");
        $db->exec("DROP TABLE IF EXISTS `".addslashes($this->table_name)."` ");
        $db->exec(
            "CREATE TABLE IF NOT EXISTS `".addslashes($this->table_name)."` (
              `IMPORT_TABLE_PRIMARY_KEY` bigint(20) NOT NULL AUTO_INCREMENT,
              `v_nr` text NOT NULL,
              `fach_nr` text NOT NULL,
              `titel` text NOT NULL,
              `ausbildungsjahr` text NOT NULL,
              `veranstaltungstyp` text NOT NULL,
              `ebene1` text NOT NULL,
              `ebene2` text NOT NULL,
              `ebene3` text NOT NULL,
              `ebene4` text NOT NULL,
              `ebene5` text NOT NULL,
              `ebene6` text NOT NULL,
              `bezeichnung` text NOT NULL,
              `ort` text NOT NULL,
              `beginn` text NOT NULL,
              `ende` text NOT NULL,
              `pruefung_schr` text NOT NULL,
              `pruefung_mdl` text NOT NULL,
              `sachbearbeitung` text NOT NULL,
              `sb_email` text NOT NULL,
              `sb_telefon` text NOT NULL,
              PRIMARY KEY (`IMPORT_TABLE_PRIMARY_KEY`)
            ) ENGINE=MyISAM ".
        "");
        $db->exec(
            "INSERT INTO Institute " .
            "SET Institut_id = MD5('Bla-Institut'), " .
                "Name = 'Bla-Institut' " .
        "");
        $db->exec(
            "INSERT INTO sem_tree " .
            "SET sem_tree_id = ".$db->quote(md5(uniqid("jgfg"))).", " .
                "parent_id = 'root', " .
                "studip_object_id = MD5('Bla-Institut') " .
        "");
        $db->exec(
            "INSERT INTO Institute " .
            "SET Institut_id = MD5('Blubb-Institut'), " .
                "Name = 'Blubb-Institut' " .
        "");
        $db->exec(
            "INSERT INTO sem_tree " .
            "SET sem_tree_id = ".$db->quote(md5(uniqid("jgfg"))).", " .
                "parent_id = 'root', " .
                "studip_object_id = MD5('Blubb-Institut') " .
        "");

        $GLOBALS['user'] = new User();
        $GLOBALS['user']->id = md5("root");
    }

    function tearDown() {
        $db = DBManager::get();
        $db->exec("DROP TABLE IF EXISTS `".addslashes($this->table_name)."` ");
        $db->exec("TRUNCATE TABLE seminare ");
        $db->exec("TRUNCATE TABLE Institute ");
        $db->exec("TRUNCATE TABLE sem_tree ");
        $db->exec("TRUNCATE TABLE seminar_sem_tree ");
    }

    function test_get_or_create_sem_tree() {
        $db = DBManager::get();
        $anzahl_sem_tree = $db->query("SELECT COUNT(*) FROM sem_tree ")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual("2", $anzahl_sem_tree);

        $table = new NSI_VeranstaltungTable($this->table_name);
        $table->getOrCreateSemTreeId("Bla-Institut", null, "bla3", "bla4", "bla5", "bla6");

        $anzahl_sem_tree = $db->query("SELECT COUNT(*) FROM sem_tree ")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual("6", $anzahl_sem_tree);

        $table->getOrCreateSemTreeId("Bla-Institut", null, "bla3", "bla4", "bla5", "bla6", true);

        $anzahl_sem_tree = $db->query("SELECT COUNT(*) FROM sem_tree ")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual("6", $anzahl_sem_tree);

        $table->getOrCreateSemTreeId("Blubb-Institut", "", null, "bla4", "bla5", "bla6");
        $anzahl_sem_tree = $db->query("SELECT COUNT(*) FROM sem_tree ")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual("9", $anzahl_sem_tree);

        $table->getOrCreateSemTreeId("Blubb-Institut", "bla4", null, null, "bla5", "bla6");
        $anzahl_sem_tree = $db->query("SELECT COUNT(*) FROM sem_tree ")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual("9", $anzahl_sem_tree);

        $table->getOrCreateSemTreeId("Blubb-Institut", "bla4", null, " ", "bla5", "bla9");
        $anzahl_sem_tree = $db->query("SELECT COUNT(*) FROM sem_tree ")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual("10", $anzahl_sem_tree);

        //Mit Ebene 1, die nicht einem Institut entspricht, soll nichts gemacht werden,
        //nur false zurück gegeben werden
        $back = $table->getOrCreateSemTreeId("falsches-Institut", "bla4", null, " ", "bla5", "bla9");
        $anzahl_sem_tree = $db->query("SELECT COUNT(*) FROM sem_tree ")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual("10", $anzahl_sem_tree);
        $this->assertEqual($back, false);
    }

}