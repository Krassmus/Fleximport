<?php

class FleximportFachDepartmentsDynamic extends FleximportDynamic
{

    public function forClassFields()
    {
        return array(
            'Fach' => array("fleximport_fach_departments" => _("Fachbereiche des Faches"))
        );
    }

    public function isMultiple()
    {
        return true;
    }

    public function applyValueBeforeStore($object, $value, $line, $sync)
    {
        $value = array_filter($value);
        $object->assignFachbereiche($value);
    }

    public function currentValue($object, $field, $sync)
    {
        return $object->departments->pluck("institut_id");
    }
}
