<?php

class FleximportCourseDateGroupsDynamic extends FleximportDynamic
{

    public function forClassFields()
    {
        return array(
            'CourseDate' => array("fleximport_dategroups" => _("Namen von Veranstaltungsgruppen"))
        );
    }

    public function isMultiple()
    {
        return true;
    }

    public function applyValue($object, $value, $line, $sync)
    {
        if ($sync) {
            $delete = DBManager::get()->prepare("
                DELETE FROM termin_related_groups
                WHERE termin_id = ?
            ");
            $delete->execute(array($object->getId()));
        }
        $insert = DBManager::get()->prepare("
            INSERT IGNORE INTO termin_related_groups
            SET termin_id = :termin_id,
                statusgruppe_id = :statusgruppe_id
        ");
        foreach ($value as $groupname) {
            $statusgruppe = Statusgruppen::findOneBySQL("range_id = :seminar_id AND name = :name OR statusgruppe_id = :name", array(
                'seminar_id' => $object['range_id'],
                'name' => $groupname
            ));
            if ($statusgruppe) {
                $insert->execute(array(
                    'termin_id' => $object->getId(),
                    'statusgruppe_id' => $statusgruppe->getId()
                ));
            }
        }
    }

    public function currentValue($object, $field, $sync)
    {
        $statement = DBManager::get()->prepare("
            SELECT statusgruppe_id
            FROM termin_related_groups
            WHERE termin_id = :termin_id
        ");
        $statement->execute(array('termin_id' => $object->getId()));
        return $statement->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}
