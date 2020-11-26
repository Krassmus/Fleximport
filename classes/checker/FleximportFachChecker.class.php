<?php

require_once __DIR__."/FleximportChecker.interface.php";

class FleximportFachChecker implements FleximportChecker
{

    public function check($data, $virtualobject, $relevantfields) {
        $errors = "";
        if ($data['fleximport_fach_departments']) {
            $data['fleximport_fach_departments'] = array_filter($data['fleximport_fach_departments']);
        }
        if (!$data['fleximport_fach_departments'] || count($data['fleximport_fach_departments']) === 0) {
            $errors .= "Es muss mindestens eine verantwortliche Einrichtung zugewiesen werden. ";
        }
        return $errors;
    }
}
