<?php

class FleximportModulResponsibleInstituteDynamic extends FleximportDynamic
{

    public function forClassFields()
    {
        return array(
            'Modul' => array('fleximport_responsible_institute' => _('Verantwortliche Einrichtung des Moduls')),
            'ModulFlexImport' => array('fleximport_responsible_institute' => _('Verantwortliche Einrichtung des Moduls')),
        );
    }

    public function isMultiple()
    {
        return false;
    }

    public function applyValueBeforeStore($object, $value, $line, $sync)
    {
        $object->assignResponsibleInstitute($value);
    }

    public function currentValue($object, $field, $sync)
    {
        return $object->responsible_institute->institut_id;
    }
}
