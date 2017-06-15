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
            "name" => "Veranstaltungsname"
        );
        $datafields = DataField::findBySQL("object_type = 'sem' ORDER BY name ASC");
        foreach ($datafields as $datafield) {
            $formats[$datafield->getId()] = _("Datenfeld")." '".$datafield['name']."'";
        }
        return $formats;
    }

    public function map($format, $value, $data) {
        switch ($format) {
            case "number":
                $course = Course::findOneBySQL("VeranstaltungsNummer = ?", array($value));
                if ($course) {
                    return $course->getId();
                }
                break;
            case "name":
                $course = Course::findOneBySQL("name = ?", array($value));
                if ($course) {
                    return $course->getId();
                }
                break;
            default:
                //Datenfeld:
                $datafield = DataField::find($format);
                if ($datafield && $datafield['object_type'] === "sem") {
                    $entry = DatafieldEntryModel::findOneBySQL("datafield_id = ? AND content = ?", array(
                        $datafield->getId(),
                        $value
                    ));
                    if ($entry) {
                        return $entry['range_id'];
                    }
                }
        }
    }

}