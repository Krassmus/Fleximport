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
            "uniquename" => "Gruppenname in range_id",
            "uniquename_semname" => "Gruppenname in Veranstaltungsname (Spalte kontext)",
            "uniquename_semnumber" => "Gruppenname in Veranstaltungsnummer (Spalte kontext)",
            "uniquename_inst" => "Gruppenname in Einrichtungsname (Spalte kontext)"
        );
    }

    public function map($format, $value, $data) {
        switch ($format) {
            case "uniquename":
                $gruppe = Statusgruppen::findOneBySQL("name = ? AND range_id = ?", array($value, $data['range_id']));
                if ($gruppe) {
                    return $gruppe->getId();
                }
                break;
            case "uniquename_semname":
                $range = Course::findOneBySQL("name = ?", array($data['kontext']));
                $gruppe = Statusgruppen::findOneBySQL("name = ? AND range_id = ?", array($value, $range->getId()));
                if ($gruppe) {
                    return $gruppe->getId();
                }
                break;
            case "uniquename_semnumber":
                $range = Course::findOneBySQL("VeranstaltungsNummer = ?", array($data['kontext']));
                $gruppe = Statusgruppen::findOneBySQL("name = ? AND range_id = ?", array($value, $range->getId()));
                if ($gruppe) {
                    return $gruppe->getId();
                }
                break;
            case "uniquename_semname":
                $range = Institute::findOneBySQL("name = ?", array($data['kontext']));
                $gruppe = Statusgruppen::findOneBySQL("name = ? AND range_id = ?", array($value, $range->getId()));
                if ($gruppe) {
                    return $gruppe->getId();
                }
                break;
        }
    }

}