<?php

class FleximportSeminar_idMapper implements FleximportMapper {

    public function getName() {
        return "Seminar_id";
    }

    public function possibleFieldnames() {
        return array("seminar_id", "range_id");
    }

    public function possibleFormats() {
        $formats = array(
            "number" => "Veranstaltungsnummer",
            "name_and_semester" => "Name und Semester",
            "number_ans_semester" => "Veranstaltunsnummer und Semester");
        $datafields = DataField::findBySQL("object_type = 'sem' ORDER BY name ASC");
        foreach ($datafields as $datafield) {
            $formats[$datafield->getId()] = _("Datenfeld")." '".$datafield['name']."'";
        }
        return $formats;
    }

    public function map($settings, $rawdata, $alreadymappeddata) {

    }

}