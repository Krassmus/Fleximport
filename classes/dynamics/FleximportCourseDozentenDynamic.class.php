<?php

class FleximportCourseDozentenDynamic implements FleximportDynamic {

    public function forClassFields()
    {
        return array(
            'Course' => array("fleximport_dozenten" => _("user_ids"))
        );
    }

    public function isMultiple()
    {
        return true;
    }

    public function applyValue($object, $value)
    {
        foreach ((array) $value as $dozent_id) {
            $seminar = new Seminar($object->getId());
            $seminar->addMember($dozent_id, 'dozent');
        }
    }

    public function currentValue($object, $field)
    {
        $select = DBManager::get()->prepare("
            SELECT user_id
            FROM seminar_user
            WHERE
                seminar_user.Seminar_id = ?
                AND seminar_user.status = 'dozent'
        ");
        $select->execute(array($object->getId()));
        return $select->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}