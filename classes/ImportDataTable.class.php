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

require_once 'lib/classes/UserManagement.class.php';

class ImportDataTable {

    protected $table_name = null;
    static $table_infos = array();
    protected $msg = array();
    protected $termin_identifier = "IMPORTED_DATE";

    public static function createTable($name, $headers, $entries = array()) {
        $db = DBManager::get();
        $db->exec("DROP TABLE IF EXISTS `".addslashes($name)."` ");
        $create_sql = "CREATE TABLE `".addslashes($name)."` (";
        $create_sql .= "`IMPORT_TABLE_PRIMARY_KEY` BIGINT NOT NULL AUTO_INCREMENT ";
        foreach ($headers as $key => $fieldname) {
            $fieldname = CSVImportProcessor::reduce_diakritika_from_iso88591($fieldname);
            $create_sql .= ", ";
            $create_sql .= "`".addslashes($fieldname)."` TEXT NOT NULL";
        }
        $create_sql .= ", PRIMARY KEY (`IMPORT_TABLE_PRIMARY_KEY`) ";
        $create_sql .= ") ENGINE=MyISAM";
        $db->exec($create_sql);

        if (function_exists("get_called_class")) {
            $class_name = get_called_class();
            return new $class_name($name);
        } elseif (isset(self::$class_name)) {
            $class_name = self::$class_name;
            return new $class_name($name);
        } else {
            trigger_error("Could not get classname in static function. Use PHP 5.3 or higher.", E_USER_WARNING);
            return new ImportDataTable($name);
        }
    }

    public function __construct($table_name = null) {
        if ($table_name) {
            $this->table_name = $table_name;
        }
    }

    public function getTableInfo() {
        if (!isset(self::$table_infos[$this->table_name])) {
            $db = DBManager::get();
            $table_info = array();
            if ($db->query("SHOW TABLES LIKE ".$db->quote($this->table_name)." ")->fetch()) {
                $table_info['fields'] = $db->query(
                    "SHOW COLUMNS FROM `".addslashes($this->table_name)."` " .
                "")->fetchAll(PDO::FETCH_COLUMN, 0);
                $table_info['entries'] = $db->query(
                    "SELECT * FROM `".addslashes($this->table_name)."` " .
                "")->fetchAll(PDO::FETCH_ASSOC);
            }
            self::$table_infos[$this->table_name] = $table_info;
        }
        return self::$table_infos[$this->table_name];
    }

    public function process($parameter = array()) {
        //muss gefüllt werden von der Unterklasse
    }

    /**
     * checks, if a given item is valid or not.
     * @param array $item_data: associative array with the data of the item
     * @return string: "" for everything is okay or the problem described in the string
     */
    public function checkEntry($item_data) {
        return "";
    }

    public function drop() {
        $db = DBManager::get();
        if ($db->exec("TRUNCATE TABLE `".addslashes($this->table_name)."`")) {
            unset(self::$table_infos[$this->table_name]);
            return true;
        } else {
            return false;
        }
    }

    public function removeErrorLines() {
        $db = DBManager::get();
        $entries = $db->query(
            "SELECT * " .
            "FROM `".addslashes($this->table_name)."` " .
        "")->fetchAll(PDO::FETCH_ASSOC);
        $wantedLines = array();
        foreach ($entries as $entry) {
            if (strlen($this->checkEntry($entry)) === 0) {
                $wantedLines[] = $entry['IMPORT_TABLE_PRIMARY_KEY'];
            }
        }
        $this->stripUnwantedLines($wantedLines);
    }

    public function stripUnwantedLines($wanted_lines) {
        $db = DBManager::get();
        foreach ($wanted_lines as $key => $line) {
            $wanted_lines[$key] = addslashes($line);
        }
        $counter = $db->exec(
            "DELETE FROM `".addslashes($this->table_name)."` " .
            "WHERE `IMPORT_TABLE_PRIMARY_KEY` NOT IN ('".implode("','", $wanted_lines)."') " .
        "");
        unset(self::$table_infos[$this->table_name]);
    }

    public function getMsg() {
        return $this->msg;
    }

    public function getUserIdByDatafield($datafield, $value) {
        $db = DBManager::get();
        $user = $db->query(
            "SELECT de.range_id, SUM(1) AS anzahl " .
            "FROM datafields_entries AS de " .
                "INNER JOIN datafields AS d ON (d.datafield_id = de.datafield_id) " .
            "WHERE d.object_type = 'user' " .
                "AND d.name = ".$db->quote($datafield)." " .
                "AND de.content = ".$db->quote($value)." " .
            "GROUP BY de.content " .
        "")->fetch();
        if ($user === false) {
            return false;
        }
        if ($user['anzahl'] != 1) {
            throw new Exception(sprintf(_("Es gibt mehrere Nutzer mit der %s-ID '%s' im System!"), $datafield, $value));
        }
        return $user['range_id'];
    }

    public function getSeminarIdByDatafield($datafield, $value) {
        $db = DBManager::get();
        $sem = $db->query(
            "SELECT de.range_id, SUM(1) AS anzahl " .
            "FROM datafields_entries AS de " .
                "INNER JOIN datafields AS d ON (d.datafield_id = de.datafield_id) " .
            "WHERE d.object_type = 'sem' " .
                "AND d.name = ".$db->quote($datafield)." " .
                "AND de.content = ".$db->quote($value)." " .
            "GROUP BY de.content " .
        "")->fetch();
        if ($sem === false) {
            return false;
        }
        if ($sem['anzahl'] != 1) {
            throw new Exception(sprintf("Es gibt mehrere Veranstaltungen mit der %s-ID '%s' im System!", $datafield, $value));
        }
        return $sem['range_id'];
    }

    public function getSeminarIdByVeranstaltungsnummer($vnummer) {
        $db = DBManager::get();
        $seminar_id = $db->query(
            "SELECT Seminar_id FROM seminare WHERE VeranstaltungsNummer = ".$db->quote($vnummer)." " .
        "")->fetch(PDO::FETCH_COLUMN, 0);
        return $seminar_id ? $seminar_id : null;
    }

    /**
     *
     * @param mixed seminar_id : false if new or id of an existing seminar
     * @return Seminar
     */
    public function createOrUpdateSeminar($seminar_id, $cdetail, $semtime) {
        global $user;
        $db = DBManager::get();
        
        $sem = new Seminar($seminar_id);
        $sem->institut_id = $cdetail['Institut'][0];
        $sem->name = $cdetail['Titel'];
        $sem->semester_start_time = $semtime['beginn']; //Beginn des Semesters
        $sem->status = $cdetail['LVTyp']; //Typ der Veranstaltung
        $sem->read_level = 1;
        $sem->write_level = 1;
        if ($GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$cdetail['LVTyp']]['class']]['bereiche']) {
            if (!$cdetail['Studienbereich']) {
                $this->msg[] = array("error", "Keine Studienbereiche");
            }
            $sem->setStudyAreas($cdetail['Studienbereich']);
        }
        if ($cdetail['Veranstaltungsnummer']) {
            $sem->seminar_number = $cdetail['Veranstaltungsnummer'];
        }
        if ($cdetail['Untertitel']) {
            $sem->subtitle = $cdetail['Untertitel'];
        }
        if (isset($cdetail['Inhalt'])) {
            $sem->description = $cdetail['Inhalt'];
        }
        if (isset($cdetail['Bemerkung'])) {
            $sem->misc = $cdetail['Bemerkung'];
        }
        if (isset($cdetail['Voraussetzung'])) {
            $sem->requirements = $cdetail['Voraussetzung'];
        }
        if (isset($cdetail['Lernorganisation'])) {
            $sem->lernorga = $cdetail['Lernorganisation'];
        }
        if (isset($cdetail['Nachweis'])) {
            $sem->leistungsnachweis = $cdetail['Nachweis'];
        }
        if (isset($cdetail['ECTS-Punkte'])) {
            $sem->ects = $cdetail['ECTS-Punkte'];
        }
        if (isset($cdetail['Ort'])) {
            $sem->location = $cdetail['Ort'];
        }
        $sem->admission_type = 3;         //3 wäre geblockt
        $sem->admission_binding = 0;      //1 wäre gesperrt, Nutzer dürften sich nicht abmelden
        $sem->admission_starttime = -1;   //-1 für gibt es nicht und ansonsten UNIX-timestamp
        $sem->admission_endtime_sem = -1; //-1 für gibt es nicht und ansonsten UNIX-timestamp
        $sem->admission_prelim = 0; //Standardwert
        $sem->visible = 1;                //1 für Veranstaltung ist auffindbar

        $sem->metadate = new MetaDate();
        $sem->metadate->setSeminarStartTime($semtime['beginn']);
        $sem->semester_start_time = $semtime['beginn'];
        $sem->semester_duration_time = 0; //ein Semester lang
        $sem->setEndSemester(0);
        if (isset($cdetail['semester_duration_time'])) {
            $sem->semester_duration_time = 0; //unbegrenzt
        }
        //$sem->semester_duration_time = $timestamp_of_end - $sem->semester_start_time; //festes Ende
        $sem->metadate->setSeminarDurationTime($semtime['ende']-$semtime['beginn']);
        $sem->metadate->seminar_id = $sem->getId();
        $success = $sem->store();

        if (!$success) {

        }
        
        //Datenfelder
        foreach ($cdetail as $type => $value) {
            if (stripos($type, "datafield.") === 0) {
                $type = str_replace("datafield.", "", $type);
                $datafield_id = $db->query("SELECT datafield_id FROM datafields WHERE name = ".$db->quote($type)." AND object_type = 'sem' ")->fetch(PDO::FETCH_COLUMN, 0);
                if ($datafield_id) {
                    if ($seminar_id) {
                        $db->exec(
                            "UPDATE datafields_entries " .
                            "SET content = ".$db->quote($value).", " .
                                "chdate = ".$db->quote(time())." " .
                            "WHERE datafield_id = ".$db->quote($datafield_id)." " .
                                "AND range_id = ".$db->quote($sem->getId())." " .
                        "");
                    } else {
                        $db->exec(
                            "INSERT IGNORE INTO datafields_entries " .
                            "SET datafield_id = ".$db->quote($datafield_id).", " .
                                "range_id = ".$db->quote($sem->getId()).", " .
                                "content = ".$db->quote($value).", " .
                                "mkdate = ".$db->quote(time()).", " .
                                "chdate = ".$db->quote(time())." " .
                        "");
                    }
                }
            }
        }

        //Termin:
        if ($cdetail['Erster Termin']) {
            //wenn es maximal einen Termin zu der Veranstaltung gibt:
            $old_id = $db->query(
                "SELECT termin_id FROM termine WHERE range_id = ".$db->quote($sem->getId())." AND content = ".$db->quote($this->termin_identifier)." " .
            "")->fetch(PDO::FETCH_COLUMN, 0);
            //Format: "Tag.Monat.Jahr Stunde:Minute" oder Unix-Timestamp
            $datum = array();
            if (!is_numeric($cdetail['Erster Termin'])) {
                preg_match("/(?P<tag>\d{1,2})\.(?P<monat>\d{1,2})\.(?P<jahr>\d{4,}) (?P<stunde>\d{1,2}):(?P<minute>\d{1,2})/", $cdetail['Erster Termin'], $datum);
                $startzeitpunkt = mktime($datum['stunde'], $datum['minute'], 0, $datum['monat'], $datum['tag'], $datum['jahr']);
            } else {
                $startzeitpunkt = $cdetail['Erster Termin'];
            }
            
            if ($cdetail['Ende erster Termin']) {
                //Format: "Stunde:Minute"
                $enddatum = array();
                preg_match("/(?P<stunde>\d{1,2}):(?P<minute>\d{1,2})/", $cdetail['Ende erster Termin'], $enddatum);
                $endzeitpunkt = mktime($enddatum['stunde'], $enddatum['minute'], 0, date("n", $startzeitpunkt), date("j", $startzeitpunkt), date("Y", $startzeitpunkt));
            } else {
                $endzeitpunkt = mktime($datum['stunde']+2, $datum['minute'], 0, $datum['monat'], $datum['tag'], $datum['jahr']);
            }
            $termin = $old_id ? new SingleDate($old_id) : new SingleDate(array('seminar_id' => $sem->getId()));
            $termin->setTime($startzeitpunkt, $endzeitpunkt);
            //$termin->setComment($this->termin_identifier); //funktioniert nicht, deshalb lieber SQL
            $termin->store();
            if (!$GLOBALS['testing']) {
                $db->exec("UPDATE `termine` SET content = ".$db->quote($this->termin_identifier)." WHERE termin_id = ".$db->quote($termin->getTerminID())." ");
            }
        }
        if ($seminar_id != $sem->getId()) { //nur, wenn die Veranstaltung auch neu ist:
            $db->query(
                "INSERT INTO folder " .
                "SET folder_id='".md5(uniqid("soMMerv0Gel"))."', " .
                        "range_id='".$sem->getId()."', " .
                        "user_id='".$user->id."', " .
                        "name='"._("Allgemeiner Dateiordner")."', " .
                        "description='"._("Ablage für allgemeine Ordner und Dokumente der Veranstaltung")."', " .
                        "mkdate='".time()."', " .
                        "chdate='".time()."'" .
            "");
        }
        //In Institute einhängen:
        $alte_institute = $db->query(
            "SELECT institut_id FROM seminar_inst WHERE seminar_id = ".$db->quote($sem->getId())." " .
        "")->fetchAll(PDO::FETCH_COLUMN, 0);
        foreach (array_diff($alte_institute, $cdetail['Institut']) as $loesch_institut) {
            $db->exec("DELETE FROM seminar_inst WHERE seminar_id = ".$db->quote($sem->getId())." AND institut_id = ".$db->quote($loesch_institut)." ");
        }
        foreach ($cdetail['Institut'] as $institut) {
            if ($institut) {
                $db->query("INSERT IGNORE INTO seminar_inst SET seminar_id = ".$db->quote($sem->getId()).", institut_id = ".$db->quote($institut));
            }
        }

        //Lehrer noch zu Kurs hinzufügen:
        $alte_dozenten = $db->query(
            "SELECT user_id FROM seminar_user WHERE Seminar_id = ".$db->quote($sem->getId())." AND status = 'dozent' " .
        "")->fetchAll(PDO::FETCH_COLUMN, 0);
        foreach (array_diff($alte_dozenten, $cdetail['Kennung Dozent']) as $loesch_lehrer) {
            $db->exec("DELETE FROM seminar_user WHERE Seminar_id = ".$db->quote($sem->getId())." AND user_id = ".$db->quote($loesch_lehrer)." ");
            $termine = $db->query(
                "SELECT termin_id FROM termine WHERE range_id = ".$db->quote($sem->getId())." " .
            "")->fetchAll(PDO::FETCH_COLUMN, 0);
            foreach ($termine as $termin_id) {
                $db->exec("DELETE FROM termin_related_persons WHERE range_id = ".$db->quote($termin_id)." AND user_id = ".$db->quote($loesch_lehrer)." ");
            }
        }
        foreach ($cdetail['Kennung Dozent'] as $teacher_userid) {
            if ($teacher_userid) {
                //Falls jemand hochgestuft wird zum Dozenten, muss er erst einmal raus fliegen:
                $db->exec(
                    "DELETE FROM seminar_user " .
                    "WHERE Seminar_id = ".$db->quote($sem->getId())." " .
                        "AND user_id = ".$db->quote($loesch_lehrer)." " .
                        "AND status != 'dozent' " .
                "");
                $db->exec(
                    "INSERT IGNORE INTO seminar_user " .
                    "SET Seminar_id = ".$db->quote($sem->getId()).", " .
                            "user_id = ".$db->quote($teacher_userid).", " .
                            "status = 'dozent', " .
                            "mkdate = ".$db->quote(time()).", " .
                            "visible = 'yes' " .
                "");
            }
        }

        return $sem;
    }

    public function createOrUpdateUser($user_id, $udetail) {
        $db = DBManager::get();
        $user = new UserManagement($user_id ? $user_id : false);
        $user_data = array();
        foreach ($udetail as $key => $detail) {
            if (stripos($key, "datafield.") === false) {
                $user_data[$key] = $detail;
            }
        }
        if ($user_id) {
            $email_changed = ($user->user_data['auth_user_md5.Email'] !== $user_data['auth_user_md5.Email']);
            $user->user_data = array_merge($user->user_data, $user_data);
            if ($email_changed) {
                $success = $user->setPassword();
            } else {
                $user->storeToDatabase();
                $success = true;
            }
        } else {
            $success = $user->createNewUser($user_data);
            if (!$success) {
                $this->msg[] = array("error", sprintf(_("Nutzer %s konnte nicht erstellt werden."), $udetail['Vorname']." ".$udetail['Nachname']));
            } else {
                //$this->msg[] = array("success", sprintf(_("Nutzer %s wurde neu im System angelegt."), $udetail['Vorname']." ".$udetail['Nachname']));
            }
        }
        if ($user->msg) {
            $message = explode("§", $user->msg);
            if ($message[0] === "error") {
                $this->msg[] = array("error", $message[1]);
            }
        }
        //Datenfelder
        foreach ($udetail as $key => $value) {
            if (stripos($key, "datafield.") === 0) {
                $type = str_replace("datafield.", "", $key);
                $datafield_id = $db->query("SELECT datafield_id FROM datafields WHERE name = ".$db->quote($type)." AND object_type = 'user' ")->fetch(PDO::FETCH_COLUMN, 0);
                if ($datafield_id) {
                    $db->exec("
                        INSERT IGNORE INTO datafields_entries
                        SET datafield_id = ".$db->quote($datafield_id).",
                            range_id = ".$db->quote($user->user_data['auth_user_md5.user_id']).",
                            content = ".$db->quote($value).",
                            mkdate = ".$db->quote(time()).",
                            chdate = ".$db->quote(time())."
                        ON DUPLICATE KEY UPDATE
                            content = ".$db->quote($value).",
                            chdate = ".$db->quote(time())."
                    ");
                }
            }
        }
        return $success;
    }

}
