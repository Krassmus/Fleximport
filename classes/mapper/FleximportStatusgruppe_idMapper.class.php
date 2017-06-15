<?php

class FleximportStatusgruppe_idMapper implements FleximportMapper {

    public function getName() {
        return "statusgruppe_id";
    }

    public function possibleFieldnames() {
        return array("statusgruppe_id");
    }

    public function possibleFormats() {
        return array(
            "uniquename" => "Gruppenname in Veranstaltung"
        );
    }

    public function map($format, $value, $data) {
        if ($format === "uniquename") {
            $gruppe = Statusgruppen::findOneBySQL("name = ? AND range_id = ?", array($value, $data['range_id']));
            if ($gruppe) {
                return $gruppe->getId();
            }
        }
    }

}