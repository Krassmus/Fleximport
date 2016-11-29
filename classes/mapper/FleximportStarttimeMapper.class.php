<?php

class FleximportStarttimeMapper implements FleximportMapper {

    public function getName() {
        return "start_time";
    }

    public function possibleFieldnames() {
        return array("start_time");
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
                return $semester ? $semester['beginn'] : null;
                break;
            case "timestamp":
                if (!is_numeric($value)) {
                    $value = strtotime($value);
                }
                $semester =  Semester::findByTimestamp($value);
                return $semester ? $semester['beginn'] : null;
                break;
            case "current":
                $semester =  Semester::findCurrent();
                return $semester ? $semester['beginn'] : null;
            case "next":
                $semester =  Semester::findCurrent();
                return $semester ? $semester['beginn'] : null;
        }
    }

}