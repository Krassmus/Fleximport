<?php

class FleximportFach_idMapper implements FleximportMapper
{

    public function getName()
    {
        return "fach_id";
    }

    public function possibleFieldnames()
    {
        return array("fach_id");
    }

    public function possibleFormats()
    {
        $formats = array(
            "name" => "Fachname",
            "fach_kurz" => "Kurzname des Fachs"
        );
        return $formats;
    }

    public function map($format, $value, $data, $sormclass)
    {
        switch ($format) {
            case "name":
                $fach = Fach::findOneBySQL("name = ?", array($value));
                return $fach ? $fach->getId() : null;
                break;
            case "fach_kurz":
                $fach = Fach::findOneBySQL("fach_kurz = ?", array($value));
                return $fach ? $fach->getId() : null;
                break;
        }
    }

}
