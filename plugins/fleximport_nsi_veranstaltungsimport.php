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

class fleximport_nsi_veranstaltungsimport extends FleximportPlugin
{

    public function getSemtreeId($parent_id, $ebene, $institut = true)
    {
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


    public function fieldsToBeMapped()
    {
        return array(
            "fleximport_studyarea"
        );
    }


    public function mapField($field, $line)
    {
        if($field === 'fleximport_studyarea') {
            return array($this->getOrCreateSemTreeId(
                $line['ebene1'],
                $line['ebene2'],
                $line['ebene3'],
                $line['ebene4'],
                $line['ebene5'],
                $line['ebene6']
            ));
        }
    }
}
