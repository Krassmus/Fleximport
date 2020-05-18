<?php

class FleximportUnixtimestampMapper implements FleximportMapper
{

    public function getName()
    {
        return "Datumsangabe";
    }

    public function possibleFieldnames()
    {
        return array("fleximport_expiration_date", "date", "begin", "beginn", "end", "end_date");
    }

    public function possibleFormats()
    {
        return array(
            "date" => "Datumsangabe"
        );
    }

    public function map($format, $value, $data, $sormclass)
    {
        switch ($format) {
            case "date":
                return strtotime($value);
                break;
        }
    }

}
