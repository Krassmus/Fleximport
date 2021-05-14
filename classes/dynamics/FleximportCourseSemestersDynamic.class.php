<?php

class FleximportCourseSemestersDynamic extends FleximportDynamic
{

    public function forClassFields()
    {
        return array(
            'Course' => array("fleximport_course_semesters" => _("Semester der Veranstaltung"))
        );
    }

    public function isMultiple()
    {
        return true;
    }

    public function applyValue($object, $value, $line, $sync)
    {
        if (StudipVersion::olderThan("4.99.99")) {
            return;
        }
        if ($sync) {
            $object->setSemesters(Semester::findMany($value));
        } else {
            $insert = DBManager::get()->prepare("
                INSERT IGNORE INTO semester_courses
                SET course_id = :course_id,
                    semester_id = :semester_id,
                    mkdate = UNIX_TIMESTAMP(),
                    chdate = UNIX_TIMESTAMP()
            ");
            foreach ($value as $semester_id) {
                $insert->execute([
                    'course_id' => $object->id,
                    'semester_id' => $semester_id,
                ]);
            }
            $object->resetRelation('semesters');
        }

        $statement = DBManager::get()->prepare("
            SELECT userdomain_id
            FROM seminar_userdomains
            WHERE seminar_id = ?
        ");
        $statement->execute(array($object->getId()));
        $olddomains = $statement->fetchAll(PDO::FETCH_COLUMN, 0);
        foreach (array_diff($value, $olddomains) as $to_add) {
            $domain = new UserDomain($to_add);
            $domain->addSeminar($object->getId());
        }
        if ($sync) {
            foreach (array_diff($olddomains, $value) as $to_remove) {
                $domain = new UserDomain($to_remove);
                $domain->removeSeminar($object->getId());
            }
        }
    }

    public function currentValue($object, $field, $sync)
    {
        if (StudipVersion::olderThan("4.99.99")) {
            return [];
        }
        $semester_ids = [];
        foreach ($object->semesters as $semester) {
            $semester_ids[] = $semester->getId();
        }
        return $semester_ids;
    }
}
