<?php

class fleximport_kiron_courses extends FleximportPlugin {

    static protected $module_institutes = array(
        //fieldname => institut_id
        "bus" => "447ca2132c444d5a5f3f60b750890347",
        "arc" => "f8d90c4d9ba5d5c26b452336f802f9e8",
        "it" => "df2df4f4417167e31d2075e0ffdba2d7",
        "cul" => "2512173ac41821dd16866b9073e432b7",
        "lang" => "3422a1629c46b312e08987ed3f50e503",
        "stge" => "6ee88d4a4ed229b02be74fc337a0100f"
    );

    public function fieldsToBeMapped()
    {
        return array(
            "start_time",
            "institut_id",
            "beschreibung",
            "fleximport_related_institutes"
        );
    }

    /**
     * @param string $field: name of the field of target table (not the imported table!) like
     * @return mixed: if no mapping should apply map to false. null maps
     * to database NULL. Any other value will map to a string value.
     */
    public function mapField($field, $line) {
        if ($field === "start_time") {
            return Semester::findCurrent()->beginn;
        }
        if ($field === "fleximport_related_institutes") {
            $institut_ids = array();
            foreach (self::$module_institutes as $studyarea => $institut_id) {
                if ($line[$studyarea]) {
                    $institut_ids[] = $institut_id;
                }
            }
            return $institut_ids;
        }
        if ($field === "institut_id") {
            foreach (self::$module_institutes as $studyarea => $institut_id) {
                if ($line[$studyarea]) {
                    return $institut_id;
                }
            }
            return "";
        }
        if ($field === "beschreibung") {
            if ($line['start date'] && (strtolower($line['start date']) !== "x")) {
                $text = "Live course! Begins: ".$line['start date']."\n\n";
                /*  $text = "(Note that some live courses can still be started after the starting date. Please inform yourself thorougly about all relevant deadlines, as they differ from course to course) \n\n"; */
            } else {
                $text = "This course can be taken at any time.\n\n";
            }
            $text .= "Level: ".$line['level']."\n\n";
            $text .= "Credits: ".$line['cp']."\n\n";
            $text .= "Link: ".$line['link']." \n";
            $text .= "(Remember to only use your @kiron-ohe.com user account!) \n\n";

            $text .= "Units: ".$line['weeks/units']."\n";
            $text .= "(Note that units are like chapters of a book, they vary in length and work effort, but help you understand the structure) \n\n";
            if ($line['description 1']) {
                $text .= "About the course: \n" . $line['description 1'] . "\n\n";
            }
            if ($line['description 2']) {
                $text .= $line['description 2'] . "\n\n";
            }
            if ($line['description 3']) {
                $text .= $line['description 3'] . "\n\n";
            }
            return $text;
        }
        return false;
    }

    public function checkLine($line)
    {
        $errors = "";

        if (!$line['code']) {
            $errors .= "Keine Veranstaltungsnummer. ";
        }

        $modulgruppen = array();
        foreach (self::$module_institutes as $studyarea => $institut_id) {
            if ($line[$studyarea]) {
                $modulgruppen[] = trim($line[$studyarea]);
            }
        }
        $number_groups = StudipStudyArea::countBySQL("info IN (?)", array($modulgruppen));

        if (!$number_groups) {
            $errors .= "Keine gültigen Modulgruppen festgelegt. ";
        }
        if (!$line['module']) {
            $errors .= "Kein Modul festgelegt. ";
        }
        return $errors;
    }

    public function afterUpdate(SimpleORMap $object, $line)
    {
        //We need to set up the module-tree here.
        //First we remove all entries and then we re-enter the current
        //connections to the module-tree.
        $modulename = $line['module'];
        $modulegroups = array();
        foreach (self::$module_institutes as $studyarea => $institut_id) {
            if ($line[$studyarea]) {
                $modulegroups[] = trim($studyarea);
            }
        }
        $remove = DBManager::get()->prepare("
            DELETE FROM seminar_sem_tree
            WHERE seminar_id = :seminar_id
        ");
        $remove->execute(array(
            'seminar_id' => $object->getId()
        ));
        foreach ($modulegroups as $modulegroup) {
            $modulegroup = StudipStudyArea::findOneBySQL("info = ?", array($modulegroup));
            if ($modulegroup) {
                $module = StudipStudyArea::findOneBySQL("parent_id = ? AND name = ?", array($modulegroup->getId(), $modulename));
                if (!$module) {
                    $module = new StudipStudyArea();
                    $module['parent_id'] = $modulegroup->getId();
                    $module['name'] = $modulename;
                    $module['info'] = "";
                    $module['type'] = 0;
                    $module->store();
                }
                $insert = DBManager::get()->prepare("
                    INSERT IGNORE INTO seminar_sem_tree
                    SET sem_tree_id = :sem_tree_id,
                        seminar_id = :seminar_id
                ");
                $insert->execute(array(
                    'sem_tree_id' => $module->getId(),
                    'seminar_id' => $object->getId()
                ));
            }
        }
    }
}