<?php


class fleximport_nsi_kurse extends FleximportPlugin
{
    public function fieldsToBeMapped()
    {
        return array('seminar_id');
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
                . "AND datafields.name = 'fach_nr' "
                . "AND datafields.object_type = 'sem' "
                . "AND seminare.veranstaltungsnummer = " . $db->quote($line['v_nr']) . ";"
            )->fetch(PDO::FETCH_BOTH);
            
            if($courseId) {
                return $courseId[0];
            } else {
                return false;
            }
        }
    }
}