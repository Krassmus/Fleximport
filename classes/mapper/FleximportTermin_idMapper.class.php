<?php

class FleximportTermin_idMapper implements FleximportMapper {

    public function getName() {
        return "termin_id";
    }

    public function possibleFieldnames() {
        return array("termin_id");
    }

    public function possibleFormats() {
        $formats = array(
            "content" => "Termin-content",
            "description" => "Termin-description",
            "raum" => "Raum des Termins"
        );
        return $formats;
    }

    public function map($format, $value, $data) {
        switch ($format) {
            case "content":
                $date = CourseDate::findOneBySQL("content = ?", array($value));
                return $date ? $date->getId() : null;
                break;
            case "description":
                $date = CourseDate::findOneBySQL("description = ?", array($value));
                return $date ? $date->getId() : null;
                break;
            case "raum":
                $date = CourseDate::findOneBySQL("raum = ?", array($value));
                return $date ? $date->getId() : null;
                break;
        }
    }

}