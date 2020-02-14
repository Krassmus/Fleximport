<?php

class FleximportFirstpartemailMapper implements FleximportMapper {

    public function getName() {
        return "Erster Teil einer Email-Adresse (bis zum @)";
    }

    public function possibleFieldnames() {
        return array("username");
    }

    public function possibleFormats() {
        $formats = array(
            'firstpartemail' => "Erster Teil einer Email-Adresse (bis zum @)"
        );
        return $formats;
    }

    public function map($format, $value, $data) {
        switch ($format) {
            case "firstpartemail":
                $parts = explode("@", $value);
                return $parts[0];
                break;
        }
    }

}
