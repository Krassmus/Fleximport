<?php

class FleximportSem_tree_idMapper implements FleximportMapper
{

    public function getName()
    {
        return "sem_tree_id";
    }

    public function possibleFieldnames()
    {
        return array("sem_tree_id", "fleximport_studyarea");
    }

    public function possibleFormats()
    {
        return array(
            "name" => "Studienbereichsname",
            "info" => "Infotext"
        );
    }

    public function map($format, $value, $data, $sormclass)
    {
        switch ($format) {
            case "name":
                $statement = DBManager::get()->prepare("
                    SELECT sem_tree_id
                    FROM sem_tree
                        LEFT JOIN Institute ON (Institute.Institut_id = sem_tree.studip_object_id)
                    WHERE (Institute.Name = :name OR sem_tree.name = :name)
                    LIMIT 1
                ");
                $statement->execute(array(
                    'name' => $value
                ));
                return $statement->fetch(PDO::FETCH_COLUMN, 0);
                break;
            case "info":
                $area = StudipStudyArea::findOneBySQL("info = ?", array($value));
                if ($area) {
                    return $area->getId();
                }
                break;
        }
    }

}
