<?php

class fleximport_kiron_courses extends FleximportPlugin {

    public function fieldsToBeMapped()
    {
        return array(
            "Seminar_id",
            "VeranstaltungsNummer",
            "ects",
            "status",
            "start_time",
            "duration_time",
            "Institut_id",
            "description",
            "fleximport_dozenten",
            "fleximport_related_institutes"
        );
    }

    /**
     * @param string $field: name of the field of target table (not the imported table!) like
     * @return mixed: if no mapping should apply map to false. null maps
     * to database NULL. Any other value will map to a string value.
     */
    public function mapField($field, $line) {
        if ($field === "Seminar_id") {
            $course = Course::findOneBySQL("VeranstaltungsNummer = ?", array($line['Code']));
            return $course ? $course->getId() : false;
        }
        if ($field === "VeranstaltungsNummer") {
            return $line['Code'];
        }
        if ($field === "ects") {
            return $line['CP'];
        }
        if ($field === "status") {
            return 1;
        }
        if ($field === "start_time") {
            return Semester::findCurrent()->beginn;
        }
        if ($field === "duration_time") {
            return -1;
        }
        if ($field === "fleximport_dozenten") {
            return array("316aa8de6b4abda391de08caebe6ca3d");
        }
        if ($field === "fleximport_related_institutes") {
            $institut_ids = array();
            if ($line['BUS']) {
                $institut_ids[] = "447ca2132c444d5a5f3f60b750890347";
            }
            if ($line['ENG']) {
                $institut_ids[] = "f8d90c4d9ba5d5c26b452336f802f9e8";
            }
            if ($line['ARC']) {
                $institut_ids[] = "d72bda6b371b372257eb69a6534a4c2a";
            }
            if ($line['IT']) {
                $institut_ids[] = "df2df4f4417167e31d2075e0ffdba2d7";
            }
            if ($line['CUL']) {
                $institut_ids[] = "2512173ac41821dd16866b9073e432b7";
            }
            if ($line['LANG']) {
                $institut_ids[] = "3422a1629c46b312e08987ed3f50e503";
            }
            if ($line['STGE']) {
                $institut_ids[] = "6ee88d4a4ed229b02be74fc337a0100f";
            }
            return $institut_ids;
        }
        if ($field === "Institut_id") {
            if ($line['BUS']) {
                return "447ca2132c444d5a5f3f60b750890347";
            }
            if ($line['ENG']) {
                return "f8d90c4d9ba5d5c26b452336f802f9e8";
            }
            if ($line['ARC']) {
                return "d72bda6b371b372257eb69a6534a4c2a";
            }
            if ($line['IT']) {
                return "df2df4f4417167e31d2075e0ffdba2d7";
            }
            if ($line['CUL']) {
                return "2512173ac41821dd16866b9073e432b7";
            }
            if ($line['LANG']) {
                return "3422a1629c46b312e08987ed3f50e503";
            }
            if ($line['STGE']) {
                return "6ee88d4a4ed229b02be74fc337a0100f";
            }
            return "";
        }
        if ($field === "description") {
            if ($line['Start date']) {
                $text = "Live course! Begins: ".$line['Start date']."\n\n";
            } else {
                $text = "Course can be taken at any time.\n\n";
            }
            $text .= "Level: ".$line['Level']."\n\n";
            $text .= "Credits: ".$line['Level']."\n\n";
            $text .= "Link: ".$line['Link']." \n\n";
            $text .= "Units: ".$line['Weeks/Units']." (Units are like chapters of a book, they vary in length and work effort) \n\n";
            $text .= "Description: ".$line['Description 1']."\n\n";
            $text .= "Description: ".$line['Description 2']."\n\n";
            $text .= "Description: ".$line['Description 3']."\n\n";
            return $text;
        }
        return false;
    }

    public function checkLine($line)
    {
        $errors = "";

        $modulgruppen = array();
        if ($line['BUS']) {
            $modulgruppen[] = trim($line['BUS']);
        }
        if ($line['ENG']) {
            $modulgruppen[] = trim($line['ENG']);
        }
        if ($line['ARC']) {
            $modulgruppen[] = trim($line['ARC']);
        }
        if ($line['IT']) {
            $modulgruppen[] = trim($line['IT']);
        }
        if ($line['CUL']) {
            $modulgruppen[] = trim($line['CUL']);
        }
        if ($line['LANG']) {
            $modulgruppen[] = trim($line['LANG']);
        }
        if ($line['STGE']) {
            $modulgruppen[] = trim($line['STGE']);
        }
        $number_groups = StudipStudyArea::countBySQL("info IN (?)", array($modulgruppen));

        if (!$number_groups) {
            $errors .= "Keine gültigen Modulgruppen festgelegt. ";
        }
        if (!$line['Module']) {
            $errors .= "Kein Modul festgelegt. ";
        }
        return $errors;
    }

    public function afterUpdate(SimpleORMap $object, $line)
    {
        $modulename = $line['Module'];
        $modulegroups = array();
        if ($line['BUS']) {
            $modulegroups[] = trim($line['BUS']);
        }
        if ($line['ENG']) {
            $modulegroups[] = trim($line['ENG']);
        }
        if ($line['ARC']) {
            $modulegroups[] = trim($line['ARC']);
        }
        if ($line['IT']) {
            $modulegroups[] = trim($line['IT']);
        }
        if ($line['CUL']) {
            $modulegroups[] = trim($line['CUL']);
        }
        if ($line['LANG']) {
            $modulegroups[] = trim($line['LANG']);
        }
        if ($line['STGE']) {
            $modulegroups[] = trim($line['STGE']);
        }
        $remove = DBManager::get()->prepare("
            DELETE FROM seminar_sem_tree
            SET seminar_id = :seminar_id
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