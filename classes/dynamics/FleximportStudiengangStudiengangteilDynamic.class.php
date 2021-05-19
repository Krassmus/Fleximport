<?php

class FleximportStudiengangStudiengangteilDynamic extends FleximportDynamic
{

    public function forClassFields()
    {
        return array(
            'Studiengang' => array('fleximport_studiengangteil' => _('Zugeordnete Studiengangteile')),
            'StudiengangFlexImport' => array('fleximport_responsible_institute' => _('Verantwortliche Einrichtung des Moduls')),
        );
    }

    public function isMultiple()
    {
        return true;
    }

    public function applyValueBeforeStore($object, $value, $line, $sync)
    {
        $value = array_filter($value);
        foreach ($value as $stgteil_id) {
            $stg_stgteil = StudiengangStgteil::find([$object->id, $stgteil_id, '']);
            if (!$stg_stgteil && $object->id) {
                $stg_stgteil = new StudiengangStgteil();
                $stg_stgteil->studiengang_id = $object->id;
                $stg_stgteil->stgteil_id = $stgteil_id;
                $stg_stgteil->stgteil_bez_id = '';
                $stg_stgteil->store();
            }
        }
    }

    public function currentValue($object, $field, $sync)
    {
        return $object->studiengangteile->pluck('stgteil_id');
    }
}
