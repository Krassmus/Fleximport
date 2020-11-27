<?php

require_once __DIR__."/FleximportChecker.interface.php";

class FleximportAbschlussChecker implements FleximportChecker
{

    public function check($data, $virtualobject, $relevantfields) {
        $errors = "";
        if (!$data['fleximport_abschluss_kategorie']) {
            $errors .= "Es muss eine Abschluss-Kategorie ausgewählt werden. ";
        }
        if (mb_strlen($data['name']) < 4) {
            $errors .= "Der Name des Abschlusses ist zu kurz (mindestens 4 Zeichen). ";
        }
        return $errors;
    }
}
