<?php

class FleximportForeignKeyMapper implements FleximportMapper
{

    public function getName()
    {
        return "fleximport_foreign_key";
    }

    public function possibleFieldnames()
    {
        return array("*");
    }

    public function possibleFormats()
    {
        $formats = array(
            "fleximport_foreign_key" => "Fleximport-Fremdschlüssel"
        );
        return $formats;
    }

    public function map($format, $value, $data, $sormclass) {
        if ($format === "fleximport_foreign_key") {
            $key = FleximportForeignKey::findOneBySQL("foreign_key = :foreign_key AND sormclass = :sormclass", [
                'foreign_key' => $value,
                'sormclass' => $sormclass
            ]);
            if ($key) {
                return $key['item_id'];
            }
        }
    }

}
