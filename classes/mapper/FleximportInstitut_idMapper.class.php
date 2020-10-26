<?php

class FleximportInstitut_idMapper implements FleximportMapper
{

    public function getName()
    {
        return "Institut_id";
    }

    public function possibleFieldnames()
    {
        return array("Institut_id", "institut_id", "inst_id", "fakultaets_id", "fakultaet_id", "range_id", "fleximport_user_inst", "fleximport_related_institutes");
    }

    public function possibleFormats()
    {
        $formats = array(
            "name" => "Einrichtungsname"
        );
        $datafields = DataField::findBySQL("object_type = 'inst' ORDER BY name ASC");
        foreach ($datafields as $datafield) {
            $formats[$datafield->getId()] = _("Datenfeld")." '".$datafield['name']."'";
        }
        return $formats;
    }

    public function map($format, $value, $data, $sormclass)
    {
        switch ($format) {
            case "name":
                $inst = Institute::findOneBySQL("Name = ?", array($value));
                if ($inst) {
                    return $inst->getId();
                }
                break;
            default:
                //Datenfeld:
                $datafield = DataField::find($format);
                if ($datafield && $datafield['object_type'] === "inst") {
                    $entry = DatafieldEntryModel::findOneBySQL("datafield_id = ? AND content = ?", array(
                        $datafield->getId(),
                        $value
                    ));
                    if ($entry) {
                        return $entry['range_id'];
                    }
                }
        }
    }

}
