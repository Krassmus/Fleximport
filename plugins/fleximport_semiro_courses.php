<?php

class fleximport_semiro_courses extends FleximportPlugin {

    public function fieldsToBeMapped()
    {
        return array(
            "fleximport_studyarea"
        );
    }

    /**
     * @param string $field: name of the field of target table (not the imported table!)
     * @param array $line : all other data from that line.
     * @return mixed: if no mapping should apply map to false. null maps
     * to database NULL. Any other value will map to a string value.
     */
    public function mapField($field, $line) {
        if ($field === "fleximport_studyarea") {
            $studienbereiche = array(
                "0" => "Webinare / eLearning",
                "1" => "Studiengebiet 1",
                "2" => "Studiengebiet 2",
                "3" => "Studiengebiet 3",
                "4" => "Studiengebiet 4",
                "5" => "Studiengebiet 5",
                "6" => "Trainingszentrum",
                "8" => "BKA / DHPOL / externe Fobi",
                "9" => "Sonstiges"
            );
            $studyareas = array();
            foreach (StudipStudyArea::findBySQL("name = ?", array($studienbereiche[$line['studienbereich']])) as $study_area) {
                $studyareas[] = $study_area->getId();
            }

            return $studyareas;
        }
        return false;
    }

    public function getDescription()
    {
        return "Studienbereiche werden dynamisch gemappt. Aus dem Feld `studienbereich` wird zum Beispiel der Wert 1 zu Studiengebiet 1, was wiederum als Name des Studienbereiches aufgefasst wird.";
    }
}

