<?php

class fleximport_karlsruhe_courses extends FleximportPlugin {

    public function fieldsToBeMapped()
    {
        return array(
            "fleximport_dozenten"
        );
    }

    /**
     * @param string $field: name of the field of target table (not the imported table!)
     * @param array $line : all other data from that line.
     * @return mixed: if no mapping should apply map to false. null maps
     * to database NULL. Any other value will map to a string value.
     */
    public function mapField($field, $line) {
        if ($field === "fleximport_dozenten") {
            $dozent_ids = array();
            $dozenten = preg_split("/\s*,\s*/", $line['dozent'], null, PREG_SPLIT_NO_EMPTY);
            foreach ($dozenten as $dozent) {
                $statement = DBManager::get()->prepare("
                    SELECT user_id
                    FROM auth_user_md5
                    WHERE Nachname = ?
                        AND perms = 'dozent'
                ");
                $statement->execute(array($dozent));
                $result = $statement->fetchAll(PDO::FETCH_COLUMN, 0);
                if (is_array($result)) {
                    $dozent_ids = array_merge($dozent_ids, $result);
                }
            }
            return $dozent_ids;
        }
        return false;
    }

    /**
     * Import and update the dates
     */
    public function afterUpdate(SimpleORMap $object, $line)
    {
        $weekdays = array(
            'so' => 0,
            'su' => 0,
            'mo' => 1,
            'di' => 2,
            'die' => 2,
            'tue' => 2,
            'mi' => 3,
            'we' => 3,
            'do' => 4,
            'thu' => 4,
            'fr' => 5,
            'fri' => 5,
            'sa' => 6
        );
        $zeiten = $line['zeit'];
        $zeiten = preg_split("/\s*,\s*/", $zeiten, null, PREG_SPLIT_NO_EMPTY);
        $singledates = array();
        $metadates = array();
        $import_type_dates = "karlsruhe_coursedates_import_".$object->getId();
        $import_type_metadates = "karlsruhe_coursemetadates_import_".$object->getId();
        foreach ($zeiten as $zeit) {
            if (!is_numeric(trim($zeit[0]))) {
                preg_match("/(\w+) (\d+):(\d+)\s*-\s*(\d+):(\d+)/", $zeit, $matches);
                $day = strtolower($matches[1]);
                if (isset($weekdays[$day])) {
                    $statement = DBManager::get()->prepare("
                        SELECT metadate_id 
                        FROM seminar_cycle_dates 
                        WHERE seminar_id = :course_id
                            AND start_time = :start_time
                            AND end_time = :end_time
                            AND weekday = :weekday
                    ");
                    $statement->execute(array(
                        'course_id' => $object->getId(),
                        'start_time' => $matches[2].":".$matches[3].":00",
                        'end_time' => $matches[4].":".$matches[5].":00",
                        'weekday' => $weekdays[$day]
                    ));
                    $found = false;
                    foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $cycle_id) {
                        if (FleximportMappedItem::findbyItemId($cycle_id, $import_type_metadates)) {
                            $found = $cycle_id;
                            break;
                        }
                    }
                    if (!$found) {
                        /*$semester = $object->end_semester;
                        $cycle = new SeminarCycleDate(); //Does not work yet
                        $cycle['seminar_id'] = $object->getId();
                        $cycle->start_hour = $matches[2];
                        $cycle->start_minute = $matches[3];
                        $cycle->end_hour = $matches[4];
                        $cycle->end_minute = $matches[5];
                        $cycle['weekday'] = $weekdays[$day];
                        $cycle['week_offset'] = 0;
                        $cycle['end_offset'] = floor(($semester->ende - $semester->beginn) / (7*24*60*60)) - 2;
                        $cycle['cycle'] = 0; //wöchentlich
                        $cycle->store();
                        $cycle_id = $cycle->getId();*/

                        $seminar = new Seminar($object->getId());
                        $cycle_id = $seminar->addCycle(array(
                            'day' => $weekdays[$day],
                            'start_stunde' => $matches[2],
                            'start_minute' => $matches[3],
                            'end_stunde' => $matches[4],
                            'end_minute' => $matches[5],
                            'week_offset' => 0,
                            'startWeek' => 0,
                            'turnus' => 0
                        ));

                        $mapped = new FleximportMappedItem();
                        $mapped['table_id'] = $import_type_metadates;
                        $mapped['item_id'] = $cycle_id;
                        $mapped->store();

                        $metadates[] = $cycle_id;
                    } else {
                        $seminar = new Seminar($object->getId());
                        $seminar->editCycle(array(
                            'cycle_id' => $found,
                            'day' => $weekdays[$day],
                            'start_stunde' => $matches[2],
                            'start_minute' => $matches[3],
                            'end_stunde' => $matches[4],
                            'end_minute' => $matches[5],
                            'week_offset' => 0,
                            'startWeek' => 0,
                            'turnus' => 0
                        ));

                        /*$semester = $object->end_semester;
                        $cycle = new SeminarCycleDate($found);
                        $cycle['seminar_id'] = $object->getId();
                        $cycle->start_hour = $matches[2];
                        $cycle->start_minute = $matches[3];
                        $cycle->end_hour = $matches[4];
                        $cycle->end_minute = $matches[5];
                        $cycle['weekday'] = $weekdays[$day];
                        $cycle['cycle'] = 0; //wöchentlich
                        $cycle['week_offset'] = 0;
                        $cycle['end_offset'] = floor(($semester->ende - $semester->beginn) / (7*24*60*60)) - 2;
                        $cycle->store();*/

                        $metadates[] = $found;
                    }
                }
            } else {
                //$zeit = explode("-", $zeit);
                preg_match("/(\d{4}-\d{1,2}-\d{1,2}\s+\d{1,2}:\d{1,2})\s*-\s(\d{4}-\d{1,2}-\d{1,2}\s+\d{1,2}:\d{1,2})/", $zeit, $matches);
                $begin = strtotime($matches[1]);
                $end = strtotime($matches[2]);
                $found = false;

                $dates = CourseDate::findBySQL("range_id = :course_id AND date = :begin AND end_time = :end", array(
                    'course_id' => $object->getId(),
                    'begin' => $begin,
                    'end' => $end
                ));
                foreach ($dates as $date) {
                    if (FleximportMappedItem::findbyItemId($date->getId(), $import_type_dates)) {
                        $found = true;
                        $singledates[] = $date->getId();
                        break;
                    }
                }

                if (!$found) {
                    $date = new CourseDate();
                    $date['range_id'] = $object->getId();
                    $date['date'] = $begin;
                    $date['end_time'] = $end;
                    $date->store();

                    $mapped = new FleximportMappedItem();
                    $mapped['table_id'] = $import_type_dates;
                    $mapped['item_id'] = $date->getId();
                    $mapped->store();

                    $singledates[] = $date->getId();
                }
            }
        }
        $items = FleximportMappedItem::findBySQL(
            "table_id = :table_id AND item_id NOT IN (:ids)",
            array(
                'table_id' => $import_type_dates,
                'ids' => $singledates ?: ""
            )
        );
        foreach ($items as $item) {
            $date = new CourseDate($item['item_id']);
            $date->delete();
            $item->delete();
        }

        $items = FleximportMappedItem::findBySQL(
            "table_id = :table_id AND item_id NOT IN (:ids)",
            array(
                'table_id' => $import_type_metadates,
                'ids' => $metadates ?: ""
            )
        );
        foreach ($items as $item) {
            $cycle = new SeminarCycleDate($item['item_id']);
            $cycle->delete();
        }

    }

    public function getDescription() {
        return "Mapped Dozenten nach dem Nachnamen. Mehrere Dozenten bitte per Komma trennen. Das Feld 'zeit' wird verwendet, um regelmäßige Termine zu erzeugen. Beim Update werden die regelmäßigen Termine auch geupdated (also eventuell auch gelöscht).";
    }
}

