<?php

class FleximportAbschlussKategorieDynamic extends FleximportDynamic
{

    public function forClassFields()
    {
        return array(
            'Abschluss' => array("fleximport_abschluss_kategorie" => _("Abschlusskategorien"))
        );
    }

    public function isMultiple()
    {
        return false;
    }

    public function applyValueBeforeStore($object, $value, $line, $sync)
    {
        $value = array_filter($value);
        foreach ($value as $v) {
            $object->assignKategorie($v);
        }
    }

    public function currentValue($object, $field, $sync)
    {
        return $object->category_assignment->kategorie_id;
    }
}
