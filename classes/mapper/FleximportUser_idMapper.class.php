<?php

class FleximportUser_idMapper implements FleximportMapper {

    public function getName() {
        return "user_id";
    }

    public function possibleFieldnames() {
        return array("user_id", "range_id", "fleximport_dozenten");
    }

    public function possibleFormats() {
        $formats = array(
            "username" => "Nutzername",
            "email" => "Email",
            'fullname' => "Vorname Nachname",
            'fullname_dozent' => "Vorname Nachname (nur Dozenten)"
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
            case "fullname":
                list($vorname, $nachname) = (array) preg_split("/\s+/", $value, null, PREG_SPLIT_NO_EMPTY);
                $user = User::findOneBySQL("Vorname = ? AND Nachname = ?", array($vorname, $nachname));
                if ($user) {
                    return $user->getId();
                } else {
                    return null;
                }
                break;
            case "fullname_dozent":
                list($vorname, $nachname) = (array) preg_split("/\s+/", $value, null, PREG_SPLIT_NO_EMPTY);
                $user = User::findOneBySQL("Vorname = ? AND Nachname = ? AND perms = 'dozent' ", array($vorname, $nachname));
                if ($user) {
                    return $user->getId();
                } else {
                    return null;
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