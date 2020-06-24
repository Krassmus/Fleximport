<?php

class FleximportWeekoffsetMapper implements FleximportMapper {

    public function getName() {
        return "week_offset";
    }

    public function possibleFieldnames() {
        return array("week_offset");
    }

    public function possibleFormats() {
        $formats = array(
            "datetime" => "Frühestem Termin"
        );
        return $formats;
    }

    public function map($format, $value, $data, $sormclass) {
        if (!is_numeric($value)) {
            $value = strtotime($value);
        }
        switch ($format) {
            case "datetime":
                $semester = Semester::findByTimestamp($value);
                if ($semester) {
                    $begin = $semester['vorles_beginn'];
                    $offset = floor(($value - $begin) / 86400 / 7);
                    return $offset;
                }
                return null;
                break;
        }
    }

}
