<?php

class FleximportResourceCategory_idMapper implements FleximportMapper
{

    public function getName()
    {
        return "resource_id";
    }

    public function possibleFieldnames()
    {
        return array("resource_id", "ressource_id", "parent_id", "range_id", "root_id", "fleximport_course_date_assignment");
    }

    public function possibleFormats()
    {
        $formats = array(
            "name" => "Name der Ressource",
            "description" => "Beschreibung der Ressource"
        );
        $statement = DBManager::get()->prepare("
            SELECT property_id, `name`
            FROM resource_property_definitions
            ORDER BY name ASC
        ");
        $statement->execute();

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $property) {
            $formats[$property['property_id']] = _("Ressourceneigenschaft")." '".$property['name']."'";
        }
        return $formats;
    }

    public function map($format, $value, $data, $sormclass)
    {
        switch ($format) {
            case "name":
                $statement = DBManager::get()->prepare("
                    SELECT id
                    FROM resources
                    WHERE name = ?
                    LIMIT 1
                ");
                $statement->execute(array($value));
                return $statement->fetch(PDO::FETCH_COLUMN, 0);
            case "description":
                $statement = DBManager::get()->prepare("
                    SELECT id
                    FROM resources
                    WHERE description = ?
                    LIMIT 1
                ");
                $statement->execute(array($value));
                return $statement->fetch(PDO::FETCH_COLUMN, 0);
            default:
                //Eigenschaft der Ressource:
                $statement = DBManager::get()->prepare("
                    SELECT resource_id
                    FROM resource_properties
                    WHERE property_id = :property_id
                        AND `state` = :value
                ");
                $statement->execute(array(
                    'property_id' => $format,
                    'value' => $value
                ));
                return $statement->fetch(PDO::FETCH_COLUMN, 0);
        }
    }

}
