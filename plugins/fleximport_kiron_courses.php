<?php

class fleximport_kiron_courses extends FleximportPlugin {

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
            if ($line['bus']) {
                $institut_ids[] = "447ca2132c444d5a5f3f60b750890347";
            }
            if ($line['eng']) {
                $institut_ids[] = "f8d90c4d9ba5d5c26b452336f802f9e8";
            }
            if ($line['arc']) {
                $institut_ids[] = "d72bda6b371b372257eb69a6534a4c2a";
            }
            if ($line['it']) {
                $institut_ids[] = "df2df4f4417167e31d2075e0ffdba2d7";
            }
            if ($line['cul']) {
                $institut_ids[] = "2512173ac41821dd16866b9073e432b7";
            }
            if ($line['lang']) {
                $institut_ids[] = "3422a1629c46b312e08987ed3f50e503";
            }
            if ($line['stge']) {
                $institut_ids[] = "6ee88d4a4ed229b02be74fc337a0100f";
            }
            return $institut_ids;
        }
        if ($field === "institut_id") {
            if ($line['bus']) {
                return "447ca2132c444d5a5f3f60b750890347";
            }
            if ($line['eng']) {
                return "f8d90c4d9ba5d5c26b452336f802f9e8";
            }
            if ($line['arc']) {
                return "d72bda6b371b372257eb69a6534a4c2a";
            }
            if ($line['it']) {
                return "df2df4f4417167e31d2075e0ffdba2d7";
            }
            if ($line['cul']) {
                return "2512173ac41821dd16866b9073e432b7";
            }
            if ($line['lang']) {
                return "3422a1629c46b312e08987ed3f50e503";
            }
            if ($line['stge']) {
                return "6ee88d4a4ed229b02be74fc337a0100f";
            }
            return "";
        }
        if ($field === "beschreibung") {
            if ($line['start date']) {
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
        if ($line['bus']) {
            $modulgruppen[] = trim($line['bus']);
        }
        if ($line['eng']) {
            $modulgruppen[] = trim($line['eng']);
        }
        if ($line['arc']) {
            $modulgruppen[] = trim($line['arc']);
        }
        if ($line['it']) {
            $modulgruppen[] = trim($line['it']);
        }
        if ($line['cul']) {
            $modulgruppen[] = trim($line['cul']);
        }
        if ($line['lang']) {
            $modulgruppen[] = trim($line['lang']);
        }
        if ($line['stge']) {
            $modulgruppen[] = trim($line['stge']);
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
        if ($line['bus']) {
            $modulegroups[] = trim($line['bus']);
        }
        if ($line['eng']) {
            $modulegroups[] = trim($line['eng']);
        }
        if ($line['arc']) {
            $modulegroups[] = trim($line['arc']);
        }
        if ($line['it']) {
            $modulegroups[] = trim($line['it']);
        }
        if ($line['cul']) {
            $modulegroups[] = trim($line['cul']);
        }
        if ($line['lang']) {
            $modulegroups[] = trim($line['lang']);
        }
        if ($line['stge']) {
            $modulegroups[] = trim($line['stge']);
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