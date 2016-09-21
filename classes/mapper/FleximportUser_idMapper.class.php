<?php

class FleximportUser_idMapper implements FleximportMapper {

    public function getName() {
        return "user_id";
    }

    public function possibleFieldnames() {
        return array("user_id", "range_id");
    }

    public function possibleFormats() {
        $formats = array(
            "username" => "Nutzername",
            "email" => "Email"
        );
        $datafields = DataField::findBySQL("object_type = 'user' ORDER BY name ASC");
        foreach ($datafields as $datafield) {
            $formats[$datafield->getId()] = _("Datenfeld")." '".$datafield['name']."'";
        }
        return $formats;
    }

    public function map($format, $value) {
        switch ($format) {
            case "username":
                $user = User::findOneByUsername($value);
                if ($user) {
                    return $user->getId();
                }
                break;
            case "email":
                $user = User::findOneByEmail($value);
                if ($user) {
                    return $user->getId();
                }
                break;
            default:
                //Datenfeld:
                $datafield = DataField::find($format);
                if ($datafield && $datafield['object_type'] === "user") {
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