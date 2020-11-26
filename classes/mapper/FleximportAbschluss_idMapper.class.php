<?php

class FleximportAbschluss_idMapper implements FleximportMapper
{

    public function getName()
    {
        return "abschluss_id";
    }

    public function possibleFieldnames()
    {
        return array("abschluss_id");
    }

    public function possibleFormats()
    {
        $formats = array(
            "name" => "Abschlussname",
            "fach_kurz" => "Kurzname des Abschlusses"
        );
        return $formats;
    }

    public function map($format, $value, $data, $sormclass)
    {
        switch ($format) {
            case "name":
                $abschluss = Abschluss::findOneBySQL("name = ?", array($value));
                return $abschluss ? $abschluss->getId() : null;
                break;
            case "fach_kurz":
                $fach = Fach::findOneBySQL("fach_kurz = ?", array($value));
                return $fach ? $fach->getId() : null;
                break;
        }
    }

}
