<?php

class FleximportSemester_idMapper implements FleximportMapper {

    public function getName() {
        return "Semester_id";
    }

    public function possibleFieldnames() {
        return array("semester_id");
    }

    public function possibleFormats() {
        $formats = array(
            "name" => "Semestername",
            "timestamp" => "Semester-Zeitpunkt",
            "current" => "Aktuelles Semester",
            "next" => "N�chstes Semester"
        );
        return $formats;
    }

    public function map($format, $value) {
        switch ($format) {
            case "name":
                $semester =  Semester::findOneByName($value);
                return $semester ? $semester->getId() : null;
                break;
            case "timestamp":
                if (!is_numeric($value)) {
                    $value = strtotime($value);
                }
                $semester =  Semester::findByTimestamp($value);
                return $semester ? $semester->getId() : null;
                break;
            case "current":
                $semester =  Semester::findCurrent();
                return $semester ? $semester->getId() : null;
            case "next":
                $semester =  Semester::findCurrent();
                return $semester ? $semester->getId() : null;
        }
    }

}