<?php

class FleximportUserExpirationdateDynamic implements FleximportDynamic {

    public function forClassFields()
    {
        return array(
            'User' => array("fleximport_expiration_date" => _("Unix-Timestamp oder ISO-Zeit"))
        );
    }

    public function isMultiple()
    {
        return false;
    }

    public function applyValue($object, $value, $line, $sync)
    {
        if (!is_numeric($value)) {
            $value = strtotime($value);
        }
        if ($value) {
            UserConfig::get($object->getId())->store("EXPIRATION_DATE", $value);
        } else {
            UserConfig::get($object->getId())->delete("EXPIRATION_DATE");
        }
    }

    public function currentValue($object, $field, $sync)
    {
        return UserConfig::get($object->getId())->getValue("EXPIRATION_DATE");
    }
}