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

require_once dirname(__file__)."/../classes/ImportDataTable.class.php";

require_once 'vendor/simpletest/mock_objects.php';
Mock::generate('perm', 'MockPerm', array("have_studip_perm", "is_fak_admin"));
Mock::generate('user', 'MockUser');

class ImportDataTableTest extends UnitTestCase {

    static $table_name = "import_data_table_test";

    function setUp() {
        $db = DBManager::get();
        $db->exec("DROP TABLE IF EXISTS `".addslashes(self::$table_name)."` ");
        $db->exec(
            "CREATE TABLE IF NOT EXISTS `termin_related_persons` ( " .
                "`range_id` varchar(32) NOT NULL, " .
                "`user_id` varchar(32) NOT NULL, " .
                "PRIMARY KEY (`range_id`,`user_id`) " .
            ") ENGINE=MyISAM ".
        "");

        $db->exec(
            "INSERT INTO datafields " .
            "SET datafield_id = MD5('test_datenfeld'), " .
                "name = 'test_datenfeld', " .
                "object_type = 'user' " .
        "");
        
        $GLOBALS['perm'] = new MockPerm();
        $GLOBALS['perm']->setReturnValue('have_perm', true); //wie Root
        $GLOBALS['perm']->setReturnValue('have_studip_perm', true); //wie Root
        $GLOBALS['perm']->setReturnValue('is_fak_admin', false); //wie Root
        $GLOBALS['user'] = new MockUser();
        $GLOBALS['user']->id = md5("user_id");

        $db->exec("TRUNCATE TABLE `auth_user_md5` ");
    }

    function tearDown() {
        $db = DBManager::get();
        $db->exec("DROP TABLE IF EXISTS `".addslashes(self::$table_name)."` ");
        $db->exec("TRUNCATE TABLE `auth_user_md5` ");
        $db->exec("TRUNCATE TABLE `user_info` ");
        $db->exec("TRUNCATE TABLE `datafields` ");
    }

    function testCreateTable() {
        $db = DBManager::get();
        $headers = array("username", "test");
        $table_name = self::$table_name;
        $table = ImportDataTable::createTable($table_name, $headers);
        $this->assertIsA($table, "ImportDataTable");
        $existiert_tabelle = $db->query("SHOW COLUMNS FROM `".addslashes($table_name)."`")->fetchAll();
        $this->assertEqual(count($existiert_tabelle), count($headers)+1);
        $db->exec("DROP TABLE IF EXISTS `".addslashes(self::$table_name)."` ");
    }

    function testDropTable() {
        $db = DBManager::get();
        $headers = array("username", "test");
        $table_name = self::$table_name;
        $table = ImportDataTable::createTable($table_name, $headers);
        $table->drop();
        $existiert_tabelle = $db->query("SHOW TABLES LIKE ".$db->quote($table_name)." ")->fetchAll();
        $this->assertEqual(count($existiert_tabelle), 0);
        $db->exec("DROP TABLE IF EXISTS `".addslashes(self::$table_name)."` ");
    }

    function test_get_user_by_datafield() {
        $db = DBManager::get();
        $table = new ImportDataTable(self::$table_name);
        $matrikel1 = 123;
        $matrikel2 = 666;
        $original_user1_id = md5('test_autor1');
        $original_user2_id = md5('test_autor2');
        $datafield = "Matrikelnummer";

        //Nutzer inklusive DILP erzeugen:
        $db->exec("INSERT INTO auth_user_md5 SET username = 'test_autor1', user_id = ".$db->quote($original_user1_id)." ");
        $db->exec("INSERT INTO auth_user_md5 SET username = 'test_autor2', user_id = ".$db->quote($original_user2_id)." ");
        $db->exec("INSERT INTO datafields SET datafield_id = MD5('test_autor_dilp'), name = ".$db->quote($datafield).", object_type = 'user', edit_perms = 'admin', view_perms = 'dozent', type = 'textline' ");
        $db->exec("INSERT INTO datafields_entries SET datafield_id = MD5('test_autor_dilp'), range_id = ".$db->quote($original_user1_id).", content = ".$db->quote($matrikel1)." ");
        $db->exec("INSERT INTO datafields_entries SET datafield_id = MD5('test_autor_dilp'), range_id = ".$db->quote($original_user2_id).", content = ".$db->quote($matrikel2)." ");

        $user_id = $table->getUserIdByDatafield($datafield, $matrikel2);
        $this->assertEqual($user_id, $original_user2_id);

        $user_id = $table->getUserIdByDatafield($datafield, $matrikel1);
        $this->assertEqual($user_id, $original_user1_id);

        $db->exec("TRUNCATE TABLE `auth_user_md5` ");
        $db->exec("TRUNCATE TABLE `datafields` ");
        $db->exec("TRUNCATE TABLE `datafields_entries` ");
        $db->exec("DROP TABLE IF EXISTS `".addslashes(self::$table_name)."` ");
    }

    function test_get_user_by_datafield_with_exception() {
        $db = DBManager::get();
        $table = new ImportDataTable(self::$table_name);
        $matrikel = 666;
        $original_user1_id = md5('test_autor1');
        $original_user2_id = md5('test_autor2');
        $datafield = "Matrikelnummer";

        //Nutzer inklusive DILP erzeugen:
        $db->exec("INSERT INTO auth_user_md5 SET username = 'test_autor1', user_id = ".$db->quote($original_user1_id)." ");
        $db->exec("INSERT INTO auth_user_md5 SET username = 'test_autor2', user_id = ".$db->quote($original_user2_id)." ");
        $db->exec("INSERT INTO datafields SET datafield_id = MD5('test_autor_dilp'), name = ".$db->quote($datafield).", object_type = 'user', edit_perms = 'admin', view_perms = 'dozent', type = 'textline' ");
        $db->exec("INSERT INTO datafields_entries SET datafield_id = MD5('test_autor_dilp'), range_id = ".$db->quote($original_user1_id).", content = ".$db->quote($matrikel)." ");
        $db->exec("INSERT INTO datafields_entries SET datafield_id = MD5('test_autor_dilp'), range_id = ".$db->quote($original_user2_id).", content = ".$db->quote($matrikel)." ");

        $this->expectException("Exception");

        $user_id = $table->getUserIdByDatafield($datafield, $matrikel);

        $db->exec("TRUNCATE TABLE `auth_user_md5` ");
        $db->exec("TRUNCATE TABLE `datafields` ");
        $db->exec("TRUNCATE TABLE `datafields_entries` ");
        $db->exec("DROP TABLE IF EXISTS `".addslashes(self::$table_name)."` ");
    }

    function test_get_user_by_datafield_with_no_matching_user() {
        $db = DBManager::get();
        $table = new ImportDataTable(self::$table_name);
        $matrikel = 45656456481518;

        //Nutzer, den es nicht geben sollte, suchen:
        $user_id = $table->getUserIdByDatafield("somewhat", $matrikel);
        $this->assertEqual($user_id, false);

        $db->exec("DROP TABLE IF EXISTS `".addslashes(self::$table_name)."` ");
    }

    function test_get_seminar_id_by_datensatznummer() {
        $db = DBManager::get();
        $table = new ImportDataTable(self::$table_name);
        $nummer1 = 123;
        $nummer2 = 666;
        $original_seminar1_id = md5('test_sem1');
        $original_seminar2_id = md5('test_sem2');
        $datensatznummer_datafield = "Datensatznummer";

        //Seminare inklusive ID erzeugen:
        $db->exec("INSERT INTO seminare SET Name = 'test_seminar1', Seminar_id = ".$db->quote($original_seminar1_id)." ");
        $db->exec("INSERT INTO seminare SET Name = 'test_seminar2', Seminar_id = ".$db->quote($original_seminar2_id)." ");
        $db->exec("INSERT INTO datafields SET datafield_id = MD5(".$db->quote($datensatznummer_datafield)."), name = ".$db->quote($datensatznummer_datafield).", object_type = 'sem', edit_perms = 'admin', view_perms = 'dozent', type = 'textline' ");
        $db->exec("INSERT INTO datafields_entries SET datafield_id = MD5(".$db->quote($datensatznummer_datafield)."), range_id = ".$db->quote($original_seminar1_id).", content = ".$db->quote($nummer1)." ");
        $db->exec("INSERT INTO datafields_entries SET datafield_id = MD5(".$db->quote($datensatznummer_datafield)."), range_id = ".$db->quote($original_seminar2_id).", content = ".$db->quote($nummer2)." ");

        $seminar_id = $table->getSeminarIdByDatafield($datensatznummer_datafield, $nummer2);
        $this->assertEqual($seminar_id, $original_seminar2_id);

        $seminar_id = $table->getSeminarIdByDatafield($datensatznummer_datafield, $nummer1);
        $this->assertEqual($seminar_id, $original_seminar1_id);

        $db->exec("TRUNCATE TABLE `seminare` ");
        $db->exec("TRUNCATE TABLE `datafields` ");
        $db->exec("TRUNCATE TABLE `datafields_entries` ");
        $db->exec("DROP TABLE IF EXISTS `".addslashes(self::$table_name)."` ");
    }

    function test_get_seminar_id_by_datensatznummer_with_exception_and_with_no_matching_seminar() {
        $db = DBManager::get();
        $table = new ImportDataTable(self::$table_name);
        $nummer1 = 123;
        $nummer2 = 666;
        $original_seminar1_id = md5('test_sem1');
        $original_seminar2_id = md5('test_sem2');
        $datensatznummer_datafield = "Datensatznummer";

        //Seminare inklusive ID erzeugen:
        $db->exec("INSERT INTO seminare SET Name = 'test_seminar1', Seminar_id = ".$db->quote($original_seminar1_id)." ");
        $db->exec("INSERT INTO seminare SET Name = 'test_seminar2', Seminar_id = ".$db->quote($original_seminar2_id)." ");
        $db->exec("INSERT INTO datafields SET datafield_id = MD5('test_sem_datensatznummer'), name = ".$db->quote($datensatznummer_datafield).", object_type = 'sem', edit_perms = 'admin', view_perms = 'dozent', type = 'textline' ");
        $db->exec("INSERT INTO datafields_entries SET datafield_id = MD5('test_sem_datensatznummer'), range_id = ".$db->quote($original_seminar1_id).", content = ".$db->quote($nummer2)." ");
        $db->exec("INSERT INTO datafields_entries SET datafield_id = MD5('test_sem_datensatznummer'), range_id = ".$db->quote($original_seminar2_id).", content = ".$db->quote($nummer2)." ");

        $seminar_id = $table->getSeminarIdByDatafield($datensatznummer_datafield, $nummer1);
        $this->assertEqual($seminar_id, false);

        $this->expectException("Exception");
        $seminar_id = $table->getSeminarIdByDatafield($datensatznummer_datafield, $nummer2);

        $db->exec("TRUNCATE TABLE `seminare` ");
        $db->exec("TRUNCATE TABLE `datafields` ");
        $db->exec("TRUNCATE TABLE `datafields_entries` ");
        $db->exec("DROP TABLE IF EXISTS `".addslashes(self::$table_name)."` ");
    }
    

    public function test_createOrUpdateSeminar() {
        $db = DBManager::get();
        $table = new ImportDataTable(self::$table_name);
        $studienbereich = md5("Test-Studienbereich");
        $datensatznummer_datafield = "Datensatznummer";
        $db->exec("INSERT IGNORE INTO sem_tree SET sem_tree_id = ".$db->quote($studienbereich).", parent_id = 'root', name = 'Test-Studienbereich' ");
        $db->exec("INSERT IGNORE INTO semester_data SET semester_id = ".$db->quote(md5("test-semester")).", name = 'Test-Semester', beginn = ".$db->quote(time()-86400*30).", ende = ".$db->quote(time()+86400*365-30)." ");
        $db->exec("TRUNCATE TABLE `datafields` ");
        $db->exec("INSERT INTO datafields SET datafield_id = MD5('test_sem_datensatznummer'), name = ".$db->quote($datensatznummer_datafield).", object_type = 'sem', edit_perms = 'admin', view_perms = 'dozent', type = 'textline' ");

        $titel1 = "Test-Veranstaltung";
        $titel2 = "nicht mehr test";
        $termin_datum = time()+(86400*7);
        $cdetail['Titel'] = $titel1;
        $cdetail['Institut'] = array(md5("Test-Veranstaltung"));
        $cdetail['Studienbereich'] = array($studienbereich);
        $cdetail['Kennung Dozent'] = array(md5("Test-Dozent"));
        $cdetail['LVTyp'] = 1;
        $cdetail['datafield.'.$datensatznummer_datafield] = "666";
        $cdetail['Erster Termin'] = (string) $termin_datum;

        $semtime = array('beginn' => time()-86400*30, 'ende' => time()+86400*365-30);
        $sem = $table->createOrUpdateSeminar(false, $cdetail, $semtime);
        $seminar_id = $sem->getId();

        $seminar_anzahl = $db->query("SELECT COUNT(*) FROM seminare WHERE Name = ".$db->quote($titel1))->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($seminar_anzahl, 1);
        $this->assertIsA($sem, "Seminar");
        $datafield_entry = $db->query(
            "SELECT de.content " .
            "FROM datafields_entries AS de " .
                "INNER JOIN datafields AS d ON (d.datafield_id = de.datafield_id) " .
            "WHERE d.name = ".$db->quote($datensatznummer_datafield)." " .
                "AND de.range_id = ".$db->quote($sem->getId())." " .
        "")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($datafield_entry, $cdetail['datafield.'.$datensatznummer_datafield]);
        /*$termin = $db->query(
            "SELECT * " .
            "FROM `termine` " .
        "")->fetch(PDO::FETCH_ASSOC);
        $this->assertEqual($termin['range_id'], $sem->getId());*/

        //Update testen:
        $cdetail['Titel'] = $titel2;
        $cdetail['datafield.'.$datensatznummer_datafield] = "777";
        $sem = $table->createOrUpdateSeminar($seminar_id, $cdetail, $semtime);

        $seminar_anzahl = $db->query("SELECT COUNT(*) FROM seminare WHERE Name = ".$db->quote($titel1))->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($seminar_anzahl, 0);
        $seminar_anzahl = $db->query("SELECT COUNT(*) FROM seminare WHERE Name = ".$db->quote($titel2))->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($seminar_anzahl, 1);
        $datafield_entry = $db->query(
            "SELECT de.content " .
            "FROM datafields_entries AS de " .
                "INNER JOIN datafields AS d ON (d.datafield_id = de.datafield_id) " .
            "WHERE d.name = ".$db->quote($datensatznummer_datafield)." " .
                "AND de.range_id = ".$db->quote($sem->getId())." " .
        "")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($datafield_entry, $cdetail['datafield.'.$datensatznummer_datafield]);

        $db->exec("TRUNCATE TABLE `semester_data` ");
        $db->exec("TRUNCATE TABLE `seminar_user` ");
        $db->exec("TRUNCATE TABLE `seminare` ");
        $db->exec("TRUNCATE TABLE `sem_tree` ");
        $db->exec("TRUNCATE TABLE `datafields` ");
        $db->exec("TRUNCATE TABLE `datafields_entries` ");
        $db->exec("TRUNCATE TABLE `termine` ");
        $db->exec("DROP TABLE IF EXISTS `".addslashes(self::$table_name)."` ");
    }
    

    function test_createOrUpdateUser() {
        $db = DBManager::get();
        $table = new ImportDataTable(self::$table_name);

        $count_user = $db->query(
            "SELECT COUNT(*) FROM auth_user_md5 " .
        "")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual("0", $count_user);

        $user_data = array(
            'auth_user_md5.username' => "rasmus.fuhse",
            'auth_user_md5.Vorname' => "Rasmus",
            'auth_user_md5.Nachname' => "Fuhse",
            'auth_user_md5.Email' => "fuhse@data-quest.de",
            'auth_user_md5.perms' => "dozent",
            'datafield.test_datenfeld' => "666"
        );
        $table->createOrUpdateUser($user_id, $user_data);

        $count_user = $db->query(
            "SELECT COUNT(*) FROM auth_user_md5 " .
        "")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual("1", $count_user);
        $user = $db->query("SELECT * FROM auth_user_md5 WHERE username = 'rasmus.fuhse' ")->fetch(PDO::FETCH_ASSOC);
        $this->assertEqual("Rasmus", $user['Vorname']);
        $datenfeldeintrag = $db->query(
            "SELECT de.content " .
            "FROM datafields_entries AS de " .
                "INNER JOIN datafields AS d ON (d.datafield_id = de.datafield_id) " .
            "WHERE de.range_id = ".$db->quote($user['user_id'])." " .
                "AND d.object_type = 'user' " .
        "")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual($datenfeldeintrag, "666");


        //update:
        $user_data['auth_user_md5.Vorname'] = "Rasmus Peer";
        $table->createOrUpdateUser($user['user_id'], $user_data);

        $count_user = $db->query(
            "SELECT COUNT(*) FROM auth_user_md5 " .
        "")->fetch(PDO::FETCH_COLUMN, 0);
        $this->assertEqual("1", $count_user);
        $user = $db->query("SELECT * FROM auth_user_md5 WHERE username = 'rasmus.fuhse' ")->fetch(PDO::FETCH_ASSOC);
        $this->assertEqual("Rasmus Peer", $user['Vorname']);
    }

    

    
}