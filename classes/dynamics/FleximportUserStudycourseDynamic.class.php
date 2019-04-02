<?php

class FleximportUserStudycourseDynamic implements FleximportDynamic {

    public function forClassFields()
    {
        return array(
            'User' => array("fleximport_user_studiengaenge" => _("Studiengang: Abschluss: Fachsemester (optional)"))
        );
    }

    public function isMultiple()
    {
        return true;
    }

    public function applyValue($object, $value, $line, $sync)
    {
        if ($sync) {
            UserStudyCourse::deleteBySQL("user_id = :user_id", array(
                'user_id' => $object->getId()
            ));
        }
        foreach ($value as $v) {
            $studiengang = null;
            $abschluss = null;
            $fachsemester = null;
            if (strpos($v, ":") !== false) {
                //Schema: "Mathematik: B.Sc.: 4"
                preg_match("/\s*([^:]+)\s*:\s*([^:]+)\s*:?\s*(\d+)?/", $v, $matches);
                $studiengang = $matches[1];
                $abschluss = $matches[2];
                $fachsemester = $matches[3] ?: 1;
            } elseif (strpos($v, " ") !== false) {
                //Schema: "Mathematik B.Sc. 4"
                preg_match("/\s*([^:]+)\s+([^:]+)\s+(\d+)?/", $v, $matches);
                $studiengang = $matches[1];
                $abschluss = $matches[2];
                $fachsemester = $matches[3] ?: 1;
            }
            if ($studiengang && $abschluss) {
                $studiengang = StudyCourse::find($studiengang) ?: StudyCourse::findOneBySQL("name = ?", array($studiengang));
                $abschluss = Degree::find($abschluss) ?: Degree::findOneBySQL("name = ?", array($abschluss));
                if ($studiengang && $abschluss) {
                    $userstudycourse = UserStudyCourse::findOneBySQL("user_id = :user_id AND studiengang_id = :fach_id AND abschluss_id = :abschluss_id", array(
                        'user_id' => $object->getId(),
                        'fach_id' => $studiengang->getId(),
                        'abschluss_id' => $abschluss->getId()
                    ));
                    if (!$userstudycourse) {
                        $userstudycourse = new UserStudyCourse();
                        $userstudycourse['user_id'] = $object->getId();
                        $userstudycourse['studiengang_id'] = $studiengang->getId();
                        $userstudycourse['abschluss_id'] = $abschluss->getId();
                    }
                    $userstudycourse['semester'] = $fachsemester;
                    $userstudycourse->store();
                }
            }
        }
    }

    public function currentValue($object, $field, $sync)
    {
        $userstudycourse = UserStudyCourse::findBySQL("user_id = :user_id", array(
            'user_id' => $object->getId()
        ));
        $output = array();
        foreach ($userstudycourse as $studycourse) {
            $output[] = $studycourse->studycourse['name'].": ".$studycourse->degree['name'].": ".$studycourse['semester'];
        }
        return $output;
    }
}