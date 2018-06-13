<?php

class FleximportCourseStudyareaDynamic implements FleximportDynamic {

    public function forClassFields()
    {
        return array(
            'Course' => array("fleximport_studyarea" => _("sem_tree_ids"))
        );
    }

    public function isMultiple()
    {
        return true;
    }

    public function applyValue($object, $value, $line, $sync)
    {
        $select = DBManager::get()->prepare("
            SELECT sem_tree_id
            FROM sem_tree
                LEFT JOIN Institute ON (Institute.Institut_id = sem_tree.studip_object_id)
            WHERE
                sem_tree.name = :name OR TRIM(sem_tree.name) = :name
                OR sem_tree_id = :name
                OR Institute.Name = :name OR TRIM(Institute.Name) = :name
        ");
        if ($sync) {
            $delete = DBManager::get()->prepare("
                DELETE FROM seminar_sem_tree
                WHERE seminar_id = ?
            ");
            $delete->execute(array($object->getId()));
        }
        $insert = DBManager::get()->prepare("
            INSERT IGNORE INTO seminar_sem_tree
            SET seminar_id = :course_id,
                sem_tree_id = :sem_tree_id
        ");

        foreach ($value as $key => $name) {
            if ($name) {
                $select->execute(array('name' => $name));

                foreach ($select->fetchAll(PDO::FETCH_COLUMN, 0) as $sem_tree_id) {
                    $insert->execute(array(
                        'course_id' => $object->getId(),
                        'sem_tree_id' => $sem_tree_id
                    ));
                }
            }
        }
    }

    public function currentValue($object, $field, $sync)
    {
        $select = DBManager::get()->prepare("
            SELECT sem_tree_id
            FROM seminar_sem_tree
            WHERE
                seminar_sem_tree.seminar_id = ?
        ");
        $select->execute(array($object->getId()));
        return $select->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}