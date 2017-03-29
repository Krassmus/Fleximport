<?php

class FleximportCourseRelatedInstitutesDynamic implements FleximportDynamic {

    public function forClassFields()
    {
        return array(
            'Course' => array("fleximport_related_institutes" => _("institut_ids"))
        );
    }

    public function isMultiple()
    {
        return true;
    }

    public function applyValue($object, $value, $line)
    {
        if (!$value) {
            $value = array($object['institut_id']);
        } else if(!in_array($object['institut_id'], $value)) {
            $value[] = $object['institut_id'];
        }
        foreach ($value as $institut_id) {
            $insert = DBManager::get()->prepare("
                INSERT IGNORE INTO seminar_inst
                SET seminar_id = :seminar_id,
                    institut_id = :institut_id
            ");
            $insert->execute(array(
                'seminar_id' => $object->getId(),
                'institut_id' => $institut_id
            ));
        }
    }

    public function currentValue($object, $field)
    {
        $select = DBManager::get()->prepare("
            INSERT IGNORE INTO seminar_inst
            SET seminar_id = ?
        ");
        $select->execute(array($object->getId()));
        $institut_ids = $select->fetchAll(PDO::FETCH_COLUMN, 0);
        if ($object['institut_id'] && !in_array($object['institut_id'], $institut_ids)) {
            $institut_ids[] = $object['institut_id'];
        }
        return $institut_ids;
    }
}