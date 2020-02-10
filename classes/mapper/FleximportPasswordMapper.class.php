<?php

class FleximportPasswordMapper implements FleximportMapper {

    public function getName() {
        return "Passwort";
    }

    public function possibleFieldnames() {
        return array("password");
    }

    public function possibleFormats() {
        $formats = array(
            'generate' => "Passwortgenerator zufällig",
            'bcrypt' => "Passwort hashen"
        );
        return $formats;
    }

    public function map($format, $value, $data) {
        switch ($format) {
            case "generate":
                return UserManagement::generate_password(8);
                break;
            case "bcrypt":
                return UserManagement::getPwdHasher()->HashPassword($value);
                break;
        }
    }

}
