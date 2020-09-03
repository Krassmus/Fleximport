<?php

class FleximportResourceCategory_idMapper implements FleximportMapper
{

    public function getName()
    {
        return "category_id";
    }

    public function possibleFieldnames()
    {
        return array("category_id");
    }

    public function possibleFormats()
    {
        $formats = array(
            "name" => "Name der Ressourcenkategorie",
            "classname" => "Klassenname der Ressourcenkategorie"
        );
        return $formats;
    }

    public function map($format, $value, $data, $sormclass)
    {
        switch ($format) {
            case "name":
                $statement = DBManager::get()->prepare("
                    SELECT id
                    FROM resource_categories
                    WHERE name = ?
                    LIMIT 1
                ");
                $statement->execute(array($value));
                return $statement->fetch(PDO::FETCH_COLUMN, 0);
            case "classname":
                $statement = DBManager::get()->prepare("
                    SELECT id
                    FROM resource_categories
                    WHERE classname = ?
                    LIMIT 1
                ");
                $statement->execute(array($value));
                return $statement->fetch(PDO::FETCH_COLUMN, 0);
        }
    }

}
