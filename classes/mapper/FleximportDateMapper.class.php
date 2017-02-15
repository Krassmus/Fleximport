<?php

class FleximportDateMapper implements FleximportMapper {

    public function getName() {
        return "date";
    }

    public function possibleFieldnames() {
        return array("date", "end_time", "start", "end");
    }

    public function possibleFormats() {
        $formats = array(
            "unixtimestamp" => "Unix-Timestamp",
            "strtotime" => "Lesbares Datum (tt.mm.jjjj 12:00)"
        );
        return $formats;
    }

    public function map($format, $value) {
        switch ($format) {
            case "unixtimestamp":
                return $value;
                break;
            case "strtotime":
                return strtotime($value);
                break;
        }
    }

}