<?php

class FleximportCourseDateAssignmentDynamic extends FleximportDynamic
{

    public function forClassFields()
    {
        return array(
            'CourseDate' => array("fleximport_course_date_assignment" => _("Raumbuchung"))
        );
    }

    public function isMultiple()
    {
        return false;
    }

    public function applyValue($object, $value, $line, $sync)
    {
        $resource_id = $this->currentValue($object, "", false);
        if ($resource_id) {
            $statement = DBManager::get()->prepare("
                UPDATE resources_assign
                SET resource_id = :resource_id,
                    chdate = UNIX_TIMESTAMP(),
                    `begin` = :begin,
                    `end` = :end
                WHERE assign_user_id = :termin_id
            ");
            $statement->execute(array(
                'resource_id' => $value,
                'termin_id' => $object->getId(),
                'begin' => $object['date'],
                'end' => $object['end_time']
            ));
        } else {
            $statement = DBManager::get()->prepare("
                INSERT INTO resources_assign
                SET assign_id = :id,
                    resource_id = :resource_id,
                    assign_user_id = :termin_id,
                    `begin` = :begin,
                    `end` = :end,
                    repeat_end = :end,
                    repeat_quantity = '0',
                    repeat_interval = '0',
                    repeat_month_of_year = '0',
                    repeat_day_of_month = '0',
                    repeat_week_of_month = '0',
                    repeat_day_of_week = '0',
                    mkdate = UNIX_TIMESTAMP(),
                    chdate = UNIX_TIMESTAMP()
            ");
            $statement->execute(array(
                'id' => md5(uniqid($object->getId())),
                'resource_id' => $value,
                'termin_id' => $object->getId(),
                'begin' => $object['date'],
                'end' => $object['end_time']
            ));
        }
    }

    public function currentValue($object, $field, $sync)
    {
        $statement = DBManager::get()->prepare("
            SELECT resource_id
            FROM resources_assign
            WHERE assign_user_id = ?
        ");
        $statement->execute(array($object->getId()));
        return $statement->fetch(PDO::FETCH_COLUMN, 0);
    }
}
