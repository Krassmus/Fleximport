<?php

require_once __DIR__."/FleximportChecker.interface.php";

class FleximportStatusgruppeUserChecker implements FleximportChecker {

    public function check($data, $virtualobject, $relevantfields) {
        $errors = "";
        if (!$data['user_id']) {
            $errors .= "Kein Nutzer gefunden. ";
        }
        if (!$data['statusgruppe_id']) {
            $errors .= "Keine passende Statusgruppe. ";
        }
        return $errors;
    }
}