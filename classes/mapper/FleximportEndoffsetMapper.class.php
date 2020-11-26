<?php

class FleximportEndoffsetMapper implements FleximportMapper
{

    public function getName()
    {
        return "end_offset";
    }

    public function possibleFieldnames()
    {
        return array("end_offset");
    }

    public function possibleFormats()
    {
        $formats = array(
            "datetime" => "Spätestem Termin"
        );
        return $formats;
    }

    public function map($format, $value, $data, $sormclass)
    {
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
