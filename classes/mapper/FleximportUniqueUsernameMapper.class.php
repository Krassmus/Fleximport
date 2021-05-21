<?php

class FleximportUniqueUsernameMapper implements FleximportMapper
{

    public function getName()
    {
        return "eindeutiger Nutzername";
    }

    public function possibleFieldnames()
    {
        return array("username");
    }

    public function possibleFormats()
    {
        $formats = array(
            "uniqueusername" => "eindeutiger Nutzername"
        );
        return $formats;
    }

    public function map($format, $value, $data, $sormclass)
    {
        if (!User::findByUsername($value)) {
            return $value;
        }
        $i = 1;
        do {
            $i++;
        } while(User::findByUsername($value.$i));
        return $value.$i;
    }

}
