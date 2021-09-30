<?php

class FleximportCourseNNDozentDynamic extends FleximportDynamic
{

    public function forClassFields()
    {
        return array(
            'Course' => array("fleximport_nn_dozent" => _("user_id"))
        );
    }

    public function isMultiple()
    {
        return false;
    }

    public function applyValue($object, $value, $line, $sync)
    {
        $select = DBManager::get()->prepare("
            SELECT user_id
            FROM seminar_user
            WHERE
                seminar_user.Seminar_id = :course_id
                AND seminar_user.status = 'dozent'
                AND user_id != :user_id
        ");
        $select->execute(array(
            'course_id' => $object->getId(),
            'user_id' => $value
        ));
        $dozenten = $select->fetchAll(PDO::FETCH_COLUMN, 0);
        if (!count($dozenten)) {
            $seminar = new Seminar($object->getId());
            $seminar->addMember($value, 'dozent');
        } else {
            $seminar = new Seminar($object->getId());
            $seminar->deleteMember($value);
        }
    }

    public function currentValue($object, $field, $sync)
    {
        return null;
    }
}
