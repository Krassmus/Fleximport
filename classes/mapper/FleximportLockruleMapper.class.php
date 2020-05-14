<?php

class FleximportLockruleMapper implements FleximportMapper {

    public function getName() {
        return "Sperrebene";
    }

    public function possibleFieldnames() {
        return array("lock_rule");
    }

    public function possibleFormats() {
        $formats = array(
            "name" => "Sperrebenenname",
            "description" => "Beschreibung der Sperrebene",
            "name_sem" => "Name der Sperrebene bei VA",
            "name_user" => "Name der Sperrebene bei Nutzern",
            "name_inst" => "Name der Sperrebene bei Einrichtungen"
        );
        return $formats;
    }

    public function map($format, $value, $data, $sormclass) {
        switch ($format) {
            case "name":
                $lockrule = LockRule::findOneBySQL("name = ?", array($value));
                return $lockrule ? $lockrule->getId() : null;
                break;
            case "description":
                $lockrule = LockRule::findOneBySQL("description = ?", array($value));
                return $lockrule ? $lockrule->getId() : null;
                break;
            case "name_sem":
                $lockrule = LockRule::findOneBySQL("object_type = 'sem' AND name = ?", array($value));
                return $lockrule ? $lockrule->getId() : null;
                break;
            case "name_user":
                $lockrule = LockRule::findOneBySQL("object_type = 'user' AND name = ?", array($value));
                return $lockrule ? $lockrule->getId() : null;
                break;
            case "name_inst":
                $lockrule = LockRule::findOneBySQL("object_type = 'inst' AND name = ?", array($value));
                return $lockrule ? $lockrule->getId() : null;
                break;
        }
    }

}
