<?php

class FleximportCourseDatesDynamic implements FleximportDynamic {

    public function forClassFields()
    {
        return array(
            'Course' => array("fleximport_course_dates" => _("Regelmäßige Termine"))
        );
    }

    public function isMultiple()
    {
        return true;
    }

    public function applyValue($object, $value, $line, $sync)
    {
        if (!$value) {
            $value = array($object['institut_id']);
        } else if(!in_array($object['institut_id'], $value)) {
            $value[] = $object['institut_id'];
        }
        $old_institutes = $this->currentValue($object, "fleximport_related_institutes", $sync);

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

        if (!is_numeric(trim($value))) {
            preg_match("/(\w+) (\d+):(\d+)\s*-\s*(\d+):(\d+)/", $value, $matches);
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

    public function currentValue($object, $field, $sync)
    {
        $cycles = SeminarCycleDate::findBySeminar($object->getId());
        $output = array();
        $days = array("so", "mo", "di", "mi", "do", "fr", "sa");
        foreach ($cycles as $cycle) {
            $output[] = $days[$cycle['day']]." ".$cycle['start_hour'].":".$cycle['start_minute']." - ".$cycle['end_hour'].":".$cycle['end_minute'];
        }
        return $output;
    }
}