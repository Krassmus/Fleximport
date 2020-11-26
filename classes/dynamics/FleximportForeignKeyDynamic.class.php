<?php

class FleximportForeignKeyDynamic extends FleximportDynamic
{

    public function forClassFields()
    {
        return array(
            '*' => array("fleximport_foreign_key" => _("Fleximport-Fremdschlüssel"))
        );
    }

    public function isMultiple()
    {
        return false;
    }

    public function applyValue($object, $value, $line, $sync)
    {
        $key = FleximportForeignKey::findOneBySQL("item_id = :item_id AND sormclass = :sormclass", [
            'item_id' => $object->getId(),
            'sormclass' => get_class($object)
        ]);
        if (!$key) {
            $key = new FleximportForeignKey();
            $key['item_id'] = $object->getId();
            $key['sormclass'] = get_class($object);
        }
        $key['foreign_key'] = $value;
        $key->store();
    }

    public function currentValue($object, $field, $sync)
    {
        $key = FleximportForeignKey::findOneBySQL("item_id = :item_id AND sormclass = :sormclass", [
            'item_id' => $object->getId(),
            'sormclass' => get_class($object)
        ]);
        return $key ? $key['foreign_key'] : null;
    }
}
