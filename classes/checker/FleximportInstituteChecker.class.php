<?php

require_once __DIR__."/FleximportChecker.interface.php";

class FleximportInstituteChecker implements FleximportChecker {

    public function check($data, $virtualobject, $relevantfields) {
        $errors = "";
        if ($virtualobject->isNew()) {
            if (!$data['fakultaets_id'] || (($data['fakultaets_id'] != $data['institut_id']) && !Institute::find($data['fakultaets_id']))) {
                $errors .= "Keine gültige Fakultät. ";
            }
        }

        return $errors;
    }
}
