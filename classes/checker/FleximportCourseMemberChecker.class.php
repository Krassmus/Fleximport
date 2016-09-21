<?php

require_once __DIR__."/FleximportChecker.interface.php";

class FleximportCourseMemberChecker implements FleximportChecker {

    public function check($data, $virtualobject, $relevantfields) {
        $errors = "";
        if (!$data['seminar_id']) {
            $errors .= "Keine Veranstaltung gefunden. ";
        }
        if (!$data['user_id']) {
            $errors .= "Kein passender Teilnehmender gefunden. ";
        }
        return $errors;
    }
}