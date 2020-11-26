<?php

class FleximportDateMapper implements FleximportMapper
{

    public function getName()
    {
        return "date";
    }

    public function possibleFieldnames()
    {
        return array("date", "end_time", "start", "end");
    }

    public function possibleFormats()
    {
        $formats = array(
            "unixtimestamp" => "Unix-Timestamp",
            "strtotime" => "Lesbares Datum (tt.mm.jjjj 12:00)"
        );
        return $formats;
    }

    public function map($format, $value, $data, $sormclass)
    {
        switch ($format) {
            case "unixtimestamp":
                return $value;
                break;
            case "strtotime":
                $timestamp = strtotime($value);
                if ($timestamp === false) {
                    $value = str_ireplace(
                        array("Mrz", "Mär", "Mai", "Okt", "Dez"),
                        array("Mar", "Mar", "May", "Oct", "Dec"),
                        $value
                    );
                    $timestamp = strtotime($value);
                }
                return $timestamp;
                break;
        }
    }

}
