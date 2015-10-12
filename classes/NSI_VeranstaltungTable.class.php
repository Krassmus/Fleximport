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

require_once dirname(__file__)."/ImportDataTable.class.php";
require_once 'lib/classes/Seminar.class.php';
require_once 'lib/raumzeit/SingleDate.class.php';
require_once 'lib/datei.inc.php';


class NSI_VeranstaltungTable extends ImportDataTable {

    static public $class_name = __CLASS__;
    
    public function process($parameter = array()) {
        $db = DBManager::get();

        //Veranstaltungen anlegen:
        $veranstaltungen = $db->query(
            "SELECT `".$this->table_name."`.* " .
            "FROM `".$this->table_name."` " .
        "")->fetchAll(PDO::FETCH_ASSOC);
        $sem_created = 0;

        foreach ($veranstaltungen as $seminar) {
            $existing_seminar_id = $this->getSeminarIdByVeranstaltungsnummerAndFachNr($seminar['v_nr'], $seminar['fach_nr']);
            $semtime = $db->query("SELECT beginn, ende FROM semester_data WHERE name = ".$db->quote($seminar['ausbildungsjahr'])." ")->fetch(PDO::FETCH_ASSOC);
            
            //Mappen der Zeile auf den allgemeinen Fall:
            $cdetail = $seminar;
            $cdetail['Titel'] = $seminar['v_nr'].": ".$seminar['titel'];
            $cdetail['Veranstaltungsnummer'] = $seminar['v_nr'];
            $cdetail['datafield.SAFO-Key'] = $seminar['v_nr'];
            $cdetail['datafield.Fachnummer'] = $seminar['fach_nr'];
            $cdetail['Ort'] = $seminar['ort'];
            if ($seminar['beginn'] || $seminar['ende']) {
                $cdetail['Lernorganisation'] = $seminar['beginn']." - ".$seminar['ende'];
            }
            if ($seminar['pruefung_schr'] || $seminar['pruefung_mdl']) {
                $cdetail['Nachweis'] = "Schriftliche Prüfungen: ".$seminar['pruefung_schr']."\n" .
                                        "Mündliche Prüfungen: ".$seminar['pruefung_mdl'];
            }
            if ($seminar['sachbearbeitung'] || $seminar['sb_mail'] || $seminar['sb_telefon']) {
                $cdetail['Bemerkung'] = _("Sachbearbeitung:")."\n".$seminar['sachbearbeitung']."\n".$seminar['sb_mail']."\n"._("Telefon:")." ".$seminar['sb_telefon'];
            }
            $institut = $db->query(
                "SELECT Institut_id FROM Institute WHERE Name = ".$db->quote($seminar['ebene1'])." " .
            "")->fetch(PDO::FETCH_COLUMN, 0);
            $cdetail['Institut'] = array($institut);

            $cdetail['LVTyp'] = $this->getVTyp($seminar['veranstaltungstyp']);
            $sem_class = $GLOBALS['SEM_TYPE'][$cdetail['LVTyp']]['class'];
            
            if ($GLOBALS['SEM_CLASS'][$sem_class]['bereiche']) {
                $studienbereich = $this->getOrCreateSemTreeId($cdetail['ebene1'],$cdetail['ebene2'],$cdetail['ebene3'],$cdetail['ebene4'],$cdetail['ebene5'],$cdetail['ebene6']);
                $cdetail['Studienbereich'] = array($studienbereich); //ja, richtig, maximal ein Eintrag in den sem_tree
            }

            $cdetail['Kennung Dozent'] = $this->getDozentenByVeranstaltungsnummer($seminar['v_nr'], $seminar['fach_nr']);

            $sem= null;
            if (count($cdetail['Kennung Dozent']) && $semtime && $institut) {
                if ($existing_seminar_id) {
                    $db->exec(
                       "DELETE FROM seminar_sem_tree " .
                       "WHERE seminar_id = ".$db->quote($existing_seminar_id)." " .
                           "AND sem_tree_id != ".$db->quote($studienbereich)." " .
                    "");
                    $created = false;
                } else {
                    $created = true;
                }
                $sem = $this->createOrUpdateSeminar($existing_seminar_id, $cdetail, $semtime);
                if ($sem && $seminar['fach_nr'] === "A") {
                    //Verwaltungsveranstaltungen, die Dozenten müssten hier als Tutoren eingetragen werden:
                    $dozenten = $db->query(
                        "SELECT DISTINCT de.range_id " .
                        "FROM datafields_entries AS de " .
                            "INNER JOIN datafields AS d ON (d.datafield_id = de.datafield_id AND d.object_type = 'user') " .
                            "INNER JOIN RRV_STUDIP_DOZENTEN AS doz ON (de.content = doz.fp_idnr) " .
                            "INNER JOIN auth_user_md5 AS u ON (u.user_id = de.range_id) " .
                        "WHERE doz.v_nr = ".$db->quote($seminar['v_nr'])." " .
                            "AND u.perms = 'dozent' " .
                            "AND d.name = 'fp_idnr' " .
                    "")->fetchAll(PDO::FETCH_COLUMN, 0);
                    foreach ($dozenten as $dozent_id) {
                        $sem->addMember($dozent_id, "tutor");
                    }
                }
            }
            
            if ($sem) {
                $sem_created++;
                if ($created) {
                    $this->msg[] = array("success", "Veranstaltung mit Datensatznummer ".$seminar['v_nr']." und Fach-Nummer ".$seminar['fach_nr']." wurde angelegt.");
                } else {
                    //$this->msg[] = array("success", "Veranstaltung mit Datensatznummer ".$seminar['v_nr']." und Fach-Nummer ".$seminar['fach_nr']." wurde bearbeitet.");
                }
            } else {
                $this->msg[] = array("error", "Konnte Veranstaltung mit Datensatznummer ".$seminar['v_nr']." und Fach-Nummer ".$seminar['fach_nr']." nicht anlegen oder bearbeiten.");
                if ($sem->message_stack['error']) {
                    foreach ($sem->message_stack['error'] as $errormessage) {
                        $this->msg[] = array("error", $errormessage);
                    }
                }
            }
        }
        $this->msg[] = array("success", $sem_created." Veranstaltungen angelegt oder aktualisiert.");
    }

    /**
     * Returns an array with all valid user_ids of teachers for a given seminar-name.
     */
    public function getDozentenByVeranstaltungsnummer($vnummer, $fach) {
        $db = DBManager::get();
        $dozenten = $db->query(
            "SELECT de.range_id " .
            "FROM datafields_entries AS de " .
                "INNER JOIN datafields AS d ON (d.datafield_id = de.datafield_id) " .
                "INNER JOIN RRV_STUDIP_DOZENTEN AS doz ON (de.content = doz.fp_idnr) " .
                "INNER JOIN auth_user_md5 AS u ON (u.user_id = de.range_id) " .
            "WHERE doz.v_nr = ".$db->quote($vnummer)." " .
                "AND doz.fach_nr = ".$db->quote($fach)." " .
                "AND u.perms = 'dozent' " .
                "AND d.name = 'fp_idnr' " .
                "AND d.object_type = 'user' " .
        "")->fetchAll(PDO::FETCH_COLUMN, 0);
        return $dozenten;
    }

    public function getSeminarIdByVeranstaltungsnummerAndFachNr($v_nr, $fach_nr) {
        $db = DBManager::get();
        $seminar_id = $db->query(
            "SELECT s.Seminar_id " .
            "FROM seminare AS s " .
                "INNER JOIN datafields_entries AS de ON (de.range_id = s.Seminar_id) " .
                "INNER JOIN datafields AS d ON (d.datafield_id = de.datafield_id) " .
            "WHERE s.VeranstaltungsNummer = ".$db->quote($v_nr)." " .
                "AND de.content = ".$db->quote($fach_nr)." " .
        "")->fetch(PDO::FETCH_COLUMN, 0);
        return $seminar_id ? $seminar_id : null;
    }

    /**
     * we want a sem_tree with the structure $ebene1 -> next ebene -> next ebene and so on
     * any ebene-variables except ebene1 may also be null to ignore this ebene.
     * @param string or null $ebene1 - $ebene6: strings of sem_tree items
     * @return string id of searched or created sem_tree_id ( of last $ebene{x} )
     */
    public function getOrCreateSemTreeId($ebene1, $ebene2 = null, $ebene3 = null, $ebene4 = null, $ebene5 = null, $ebene6 = null) {
        $ebenen = array(trim($ebene1));
        $ebene2 && trim($ebene2) && ($ebenen[] = trim($ebene2));
        $ebene3 && trim($ebene3) && ($ebenen[] = trim($ebene3));
        $ebene4 && trim($ebene4) && ($ebenen[] = trim($ebene4));
        $ebene5 && trim($ebene5) && ($ebenen[] = trim($ebene5));
        $ebene6 && trim($ebene6) && ($ebenen[] = trim($ebene6));

        $sem_tree_root_id = 'root';
        $db = DBManager::get();
        $sem_tree_id = $db->query(
            "SELECT e".count($ebenen).".sem_tree_id " .
            "FROM sem_tree AS e1 " .
                "INNER JOIN Institute AS i ON (i.Institut_id = e1.studip_object_id) " .
                (count($ebenen) > 1 ? "INNER JOIN sem_tree AS e2 ON (e1.sem_tree_id = e2.parent_id) " : "") .
                (count($ebenen) > 2 ? "INNER JOIN sem_tree AS e3 ON (e2.sem_tree_id = e3.parent_id) " : "") .
                (count($ebenen) > 3 ? "INNER JOIN sem_tree AS e4 ON (e3.sem_tree_id = e4.parent_id) " : "") .
                (count($ebenen) > 4 ? "INNER JOIN sem_tree AS e5 ON (e4.sem_tree_id = e5.parent_id) " : "") .
                (count($ebenen) > 5 ? "INNER JOIN sem_tree AS e6 ON (e5.sem_tree_id = e6.parent_id) " : "") .
            "WHERE e1.parent_id = ".$db->quote($sem_tree_root_id)." " .
                "AND i.Name = ".$db->quote($ebenen[0])." " .
                (count($ebenen) > 1 ? "AND e2.name = ".$db->quote($ebenen[1])." " : "") .
                (count($ebenen) > 2 ? "AND e3.name = ".$db->quote($ebenen[2])." " : "") .
                (count($ebenen) > 3 ? "AND e4.name = ".$db->quote($ebenen[3])." " : "") .
                (count($ebenen) > 4 ? "AND e5.name = ".$db->quote($ebenen[4])." " : "") .
                (count($ebenen) > 5 ? "AND e6.name = ".$db->quote($ebenen[5])." " : "") .
        "")->fetch(PDO::FETCH_COLUMN, 0);
        if (!$sem_tree_id) {
            $parent_id = $sem_tree_root_id;
            foreach ($ebenen as $key => $ebene) {
                $sem_tree_id = $this->getSemtreeId($parent_id, $ebene, $key === 0);
                if (!$sem_tree_id && $key === 0) {
                    //die oberste Ebene muss einem Institut zugeordnet sein
                    return false;
                } elseif (!$sem_tree_id) {
                    $sem_tree_id = md5(uniqid("hey hey WiKi"));
                    $db->exec(
                        "INSERT INTO sem_tree " .
                        "SET sem_tree_id = ".$db->quote($sem_tree_id).", " .
                            "parent_id = ".$db->quote($parent_id).", " .
                            "name = ".$db->quote($ebene)." " .
                    "");
                }
                $parent_id = $sem_tree_id;
            }
        }
        return $sem_tree_id;
    }

    public function getSemtreeId($parent_id, $ebene, $institut = true) {
        $db = DBManager::get();
        return $db->query(
            "SELECT st.sem_tree_id " .
            "FROM sem_tree AS st " .
            ($institut ? "INNER JOIN Institute AS i ON (st.studip_object_id = i.Institut_id) " : "") .
            "WHERE " .
            (!$institut
                ? "st.name = ".$db->quote($ebene)." "
                : "i.Name = ".$db->quote($ebene)." ") .
            "AND st.parent_id = ".$db->quote($parent_id)." " .
        "")->fetch(PDO::FETCH_COLUMN, 0);
    }

    
    public function checkEntry($item_data) {
        $output = "";
        $db = DBManager::get();
        if (!trim($item_data['v_nr'])) {
            $output .= "Keine v_nr angegeben. ";
        }
        $semester = $db->query("SELECT * FROM semester_data WHERE name = ".$db->quote($item_data['ausbildungsjahr'])." ")->fetch(PDO::FETCH_ASSOC);
        if (!$semester) {
            $output .= "Ausbildungsjahr unbekannt. ";
        }
        if ($this->getVTyp($item_data['veranstaltungstyp']) === false) {
            $output .= "Veranstaltungstyp ist unbekannt. ";
        }
        
        $institut_id = $db->query("SELECT Institut_id FROM Institute WHERE Name = ".$db->quote($item_data['ebene1'])." ")->fetch(PDO::FETCH_COLUMN, 0);
        if (!$institut_id) {
            $output .= $item_data['ebene1']." von ebene1 ist kein Institut. ";
        }
        
        $dozenten = $db->query(
            "SELECT * " .
            "FROM RRV_STUDIP_DOZENTEN " .
            "WHERE v_nr = ".$db->quote($item_data['v_nr'])." " .
                "AND fach_nr = ".$db->quote($item_data['fach_nr'])." " .
        "")->fetchAll(PDO::FETCH_ASSOC);
        if (count($dozenten) === 0) {
            $output .= "Keine Dozenten zu dieser Veranstaltung. ";
        }

        $ebenone = $this->getSemtreeId("root", $item_data['ebene1'], true);
        if (!$ebenone) {
            $output .= "Ebene1 existiert nicht im SemTree als Institut. ";
        }

        return $output;
    }
    
    protected function getVTyp($name) {
        foreach ($GLOBALS['SEM_TYPE'] as $key => $sem_type) {
            if ($sem_type['name'] === gettext(trim($name))) {
                return $key;
            }
        }
        return false;
    }
    
    
}
