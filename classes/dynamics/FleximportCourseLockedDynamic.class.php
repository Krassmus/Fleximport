<?php

class FleximportCourseLockedDynamic implements FleximportDynamic {

    public function forClassFields()
    {
        return array(
            'Course' => array("fleximport_locked" => _("1 f�r gesperrt"))
        );
    }

    public function isMultiple()
    {
        return false;
    }

    public function applyValue($object, $value, $line)
    {
        //Lock or unlock course
        if ($value) {
            CourseSet::addCourseToSet(
                CourseSet::getGlobalLockedAdmissionSetId(),
                $object->getId()
            );
        } elseif (in_array($value, array("0", 0)) && ($value !== "")) {
            CourseSet::removeCourseFromSet(
                CourseSet::getGlobalLockedAdmissionSetId(),
                $object->getId()
            );
        }
    }

    public function currentValue($object, $field)
    {
        $courseset = CourseSet::getSetForCourse($object->getId());
        return ($courseset && ($courseset->getId() === CourseSet::getGlobalLockedAdmissionSetId())) ? 1 : 0;
    }
}