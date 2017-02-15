<?php

require_once __DIR__."/FleximportChecker.interface.php";

class FleximportCourseDateChecker implements FleximportChecker {

    public function check($data, $virtualobject, $relevantfields) {
        $errors = "";
        if (!$data['range_id']) {
            $errors .= "Keine range_id. ";
        }
        if (!$data['date']) {
            $errors .= "Keine Startzeit. ";
        }
        if (!$data['end_time']) {
            $errors .= "Keine Endzeit. ";
        } elseif($data['end_time'] < $data['date']) {
            $errors .= "Endzeit liegt vor der Startzeit. ";
        }
        return $errors;
    }
}