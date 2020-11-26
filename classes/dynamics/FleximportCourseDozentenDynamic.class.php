<?php

class FleximportCourseDozentenDynamic extends FleximportDynamic
{

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

    public function applyValue($object, $value, $line, $sync)
    {
        $old_dozenten = $this->currentValue($object, "fleximport_dozenten", $sync);
        $seminar = new Seminar($object->getId());
        foreach ((array) $value as $dozent_id) {
            if ($dozent_id) {
                $seminar->addMember($dozent_id, 'dozent');
            }
        }
        if ($sync) {
            foreach (array_diff($old_dozenten, $value) as $dozent_id) {
                $seminar->deleteMember($dozent_id);
            }
        }
    }

    public function currentValue($object, $field, $sync)
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
