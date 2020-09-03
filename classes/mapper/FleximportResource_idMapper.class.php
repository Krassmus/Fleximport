<?php

class FleximportResource_idMapper implements FleximportMapper
{

    public function getName()
    {
        return "resource_id";
    }

    public function possibleFieldnames()
    {
        return array(
            "resource_id", "ressource_id", "parent_id", "range_id",
            "root_id", "fleximport_course_date_assignment", "fleximport_resource_id"
        );
    }

    public function possibleFormats()
    {
        $formats = array(
            "name" => "Name der Ressource",
            "description" => "Beschreibung der Ressource"
        );
        if (StudipVersion::newerThan("4.4.99")) {
            $statement = DBManager::get()->prepare("
                SELECT property_id, `name`
                FROM resource_property_definitions
                ORDER BY name ASC
            ");
            $statement->execute();
        } else {
            //This is for Stud.IP <= 4.4 with the old resources. We probably don't need this code, but here it is anyway:
            $statement = DBManager::get()->prepare("
                SELECT property_id, `name`
                FROM resources_properties
                ORDER BY name ASC
            ");
            $statement->execute();
        }

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $property) {
            $formats[$property['property_id']] = _("Ressourceneigenschaft")." '".$property['name']."'";
        }
        return $formats;
    }

    public function map($format, $value, $data, $sormclass)
    {
        switch ($format) {
            case "name":
                if (StudipVersion::newerThan("4.4.99")) {
                    $statement = DBManager::get()->prepare("
                        SELECT id
                        FROM resources
                        WHERE name = ?
                        LIMIT 1
                    ");
                } else {
                    //This is for Stud.IP < 4.5 with the old resources. We probably don't need this code, but here it is anyway:
                    $statement = DBManager::get()->prepare("
                        SELECT resource_id
                        FROM resources_objects
                        WHERE name = ?
                        LIMIT 1
                    ");
                }
                $statement->execute(array($value));
                return $statement->fetch(PDO::FETCH_COLUMN, 0);
            case "description":
                if (StudipVersion::newerThan("4.4.99")) {
                    $statement = DBManager::get()->prepare("
                        SELECT id
                        FROM resources
                        WHERE description = ?
                        LIMIT 1
                    ");
                } else {
                    //This is for Stud.IP < 4.5 with the old resources. We probably don't need this code, but here it is anyway:
                    $statement = DBManager::get()->prepare("
                        SELECT resource_id
                        FROM resources_objects
                        WHERE description = ?
                        LIMIT 1
                    ");
                }
                $statement->execute(array($value));
                return $statement->fetch(PDO::FETCH_COLUMN, 0);
            default:
                //Eigenschaft der Ressource:
                if (StudipVersion::newerThan("4.4.99")) {
                    $statement = DBManager::get()->prepare("
                        SELECT resource_id
                        FROM resources_properties
                        WHERE property_id = :property_id
                            AND `state` = :value
                    ");
                } else {
                    //This is for Stud.IP < 4.5 with the old resources. We probably don't need this code, but here it is anyway:
                    $statement = DBManager::get()->prepare("
                        SELECT resource_id
                        FROM resources_objects_properties
                        WHERE property_id = :property_id
                            AND `state` = :value
                    ");
                }
                $statement->execute(array(
                    'property_id' => $format,
                    'value' => $value
                ));
                return $statement->fetch(PDO::FETCH_COLUMN, 0);
        }
    }

}
