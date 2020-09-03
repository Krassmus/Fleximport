<?php

require_once __DIR__."/FleximportChecker.interface.php";

class FleximportResourceChecker implements FleximportChecker {

    public function check($data, $virtualobject, $relevantfields) {
        $errors = "";
        if (!$data['parent_id']) {
            $errors .= "Keine parent_id. ";
        }
        if (!$data['category_id']) {
            $errors .= "Keine category_id. ";
        }
        return $errors;
    }
}
