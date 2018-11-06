<?php
/*
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Rasmus Fuhse <fuhse@data-quest.de>
 * @author      Moritz Strohm <strohm@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP-Plugins
 * @since       4.0
 */
 
class fleximport_nsi_kurse extends FleximportPlugin
{
    
    //taken from NSI_Import plugin and modified:
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
    
    
    //taken from NSI_Import plugin
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
                    $statement = DBManager::get()->prepare("
                        INSERT IGNORE INTO sem_tree
                        SET sem_tree_id = :sem_tree_id,
                            parent_id = :parent_id,
                            name = :name
                    ");
                    $statement->execute(array(
                        'sem_tree_id' => $sem_tree_id,
                        'parent_id' => $parent_id,
                        'name' => $ebene
                    ));
                    /*$db->exec(
                        "INSERT INTO sem_tree " .
                        "SET sem_tree_id = ".$db->quote($sem_tree_id).", " .
                            "parent_id = ".$db->quote($parent_id).", " .
                            "name = ".$db->quote($ebene)." " .
                    "");*/
                }
                $parent_id = $sem_tree_id;
            }
        }
        return $sem_tree_id;
    }
    
    
    
    //own code starts here

    /**
     * Clean up old semesters and their data
     */
    public function afterDataFetching()
    {
        //$old_semesters = array("2011/12", "2012/13", "2013/14", "2014/15", "2015/16", "2016/17");
        $old_semesters = preg_split("/\s+/", FleximportConfig::get("NSI_OLD_SEMESTERS"), -1, PREG_SPLIT_NO_EMPTY);
        if (count($old_semesters)) {
            $statement = DBManager::get()->prepare("
                DELETE FROM `fleximport_nsi_kurse`
                WHERE `ausbildungsjahr` IN (:old_semesters)
            ");
            $statement->execute(array(
                'old_semesters' => $old_semesters
            ));
        }
    }
    
    public function fieldsToBeMapped()
    {
        return array(
            "seminar_id",
            "fleximport_studyarea",
            "fleximport_dozenten",
            "leistungsnachweis",
            "sonstiges",
            "lernorga"
        );
    }
    
    
    public function mapField($field, $line)
    {
        if($field === 'seminar_id') {
            $db = DBManager::get();
            $courseId = $db->query(
                  "SELECT seminare.seminar_id FROM seminare "
                . "INNER JOIN datafields_entries "
                . "ON seminare.seminar_id = datafields_entries.range_id "
                . "INNER JOIN datafields "
                . "ON datafields.datafield_id = datafields_entries.datafield_id "
                . "WHERE "
                . "datafields_entries.content = " . $db->quote($line['fach_nr']) . " "
                . "AND datafields.name = 'Fachnummer' "
                . "AND datafields.object_type = 'sem' "
                . "AND seminare.veranstaltungsnummer = " . $db->quote($line['v_nr']) . ";"
            )->fetch(PDO::FETCH_BOTH);
            
            if($courseId) {
                return $courseId[0];
            } else {
                return false;
            }
        } elseif($field === 'fleximport_studyarea') {
            return array($this->getOrCreateSemTreeId(
                $line['ebene1'],
                $line['ebene2'],
                $line['ebene3'],
                $line['ebene4'],
                $line['ebene5'],
                $line['ebene6']
            ));
        } elseif($field === "fleximport_dozenten") {
            $statement = DBManager::get()->prepare("
                SELECT de.range_id 
                FROM datafields_entries AS de 
                    INNER JOIN datafields AS d ON (d.datafield_id = de.datafield_id) 
                    INNER JOIN fleximport_nsi_vdozenten AS doz ON (de.content = doz.fp_idnr) 
                    INNER JOIN auth_user_md5 AS u ON (u.user_id = de.range_id)
                WHERE doz.v_nr = :v_nr 
                    AND doz.fach_nr =  :fach_nr
                    AND u.perms = 'dozent' 
                    AND d.object_type = 'user' 
                    AND d.name = 'fp_idnr' 
            ");
            $statement->execute(array(
                'v_nr' => $line['v_nr'],
                'fach_nr' => $line['fach_nr']
            ));
            return $statement->fetchAll(PDO::FETCH_COLUMN, 0);
        } elseif($field === "leistungsnachweis") {
            if ($line['pruefung_schr'] || $line['pruefung_mdl']) {
                return "Schriftliche Prüfungen: ".$line['pruefung_schr']."\n" .
                "Mündliche Prüfungen: ".$line['pruefung_mdl'];
            } else {
                return "";
            }
        } elseif ($field === "sonstiges") {
            if ($line['sachbearbeitung'] || $line['sb_mail'] || $line['sb_telefon']) {
                return _("Sachbearbeitung:")."\n".$line['sachbearbeitung']."\n".$line['sb_mail']."\n"._("Telefon:")." ".$line['sb_telefon'];
            } else {
                return "";
            }
        } elseif($field === "lernorga") {
            if ($line['beginn'] || $line['ende']) {
                return $line['beginn']." - ".$line['ende'];
            } else {
                return "";
            }
        }
    }
}