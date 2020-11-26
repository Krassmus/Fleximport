<?php

require_once __DIR__."/FleximportChecker.interface.php";

class FleximportUserChecker implements FleximportChecker
{

    public function check($data, $virtualobject, $relevantfields) {
        $errors = "";
        if ($virtualobject->isNew()) {
            if (!$data['username']) {
                $errors .= "Kein Nutzername. ";
            } else {
                $validator = new email_validation_class;
                if (!$validator->ValidateUsername($data['username'])) {
                    $errors .= "Nutzername syntaktisch falsch. ";
                } elseif (get_userid($data['username']) && get_userid($data['username']) !== $data['user_id']) {
                    $errors .= "Nutzername schon vergeben. ";
                }
            }
            if (!$data['perms'] || !in_array($data['perms'], array("user", "autor", "tutor", "dozent", "admin", "root"))) {
                $errors .= "Keine korrekten Perms gesetzt. ";
            }
            if (!$data['vorname'] && !$data['nachname']) {
                $errors .= "Kein Name gesetzt. ";
            }
        }
        if (in_array("email", $relevantfields)) {
            if (!$data['email']) {
                $errors .= "Keine Email. ";
            } else {
                $validator = new email_validation_class;
                if (!$validator->ValidateEmailAddress($data['email'])) {
                    $errors .= "Email syntaktisch falsch. ";
                }
            }
        }
        return $errors;
    }
}
