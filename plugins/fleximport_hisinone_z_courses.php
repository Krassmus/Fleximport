<?php

require_once __DIR__."/HisInOne/Soap.php";
require_once __DIR__."/HisInOne/SoapClient.php";
require_once __DIR__."/HisInOne/DataMapper.php";

class fleximport_hisinone_z_courses extends FleximportPlugin
{

    public function customImportEnabled()
    {
        return true;
    }

    public function neededConfigs()
    {
        return [
            "HISINONE_REGULAR_DATETYPES"
        ];
    }

    public function neededProcessConfigs()
    {
        return array("HISINONE_TERMKEY");
    }

    public function fetchData()
    {
        if (!$this->table->process->getConfig("HISINONE_TERMKEY")) {
            PageLayout::postInfo(_("Es fehlt die Konfiguration HISINONE_TERMKEY."));
            return null;
        }

        $data = $this->getCoursesData((int) $this->table->process->getConfig("HISINONE_TERMKEY"));
        //var_dump($data); die();
        if ($data) {
            list($fields, $courses) = \HisInOne\DataMapper::getData($data->course);

            $fields[] = "heimatinstitut_lid";
            $fields[] = "institut_lids";
            $fields[] = "teachers_usernames";
            $fields[] = "teachers_ids";
            $regular_dates_fields = [
                'course_id',
                'coursename',
                'planneddatesid',
                'rhythm__id',
                'rhythm__key',
                'rhythm__label',
                'rhythm__hiskeyid',
                'weekday__id',
                'weekday__key',
                'weekday__label',
                'weekday_hiskeyid',
                'firstappointment',
                'lastappointment',
                'time__start',
                'time__end',
                'time_academictimespecification',
                'expectedattendees',
                'notice',
                'room__roomid',
                'room__room',
                'room__roomkey',
                'room__floor',
                'room__floorkey',
                'room__building',
                'room__buildingkey',
                'room__campus',
                'room__campuskey',
                'exdates'
            ];
            $regular_dates = [];

            $individual_dates_fields = [
                'course_id',
                'coursename',
                'id',
                'metadate_id',
                'date',
                'remark',
                'from',
                'to',
                'weekdayId',
                'roomId',
                'room__roomid',
                'room__room',
                'room__roomkey',
                'room__floor',
                'room__floorkey',
                'room__building',
                'room__buildingkey',
                'room__campus',
                'room__campuskey',
                'begin',
                'end',
                'series_id'
            ];
            $individual_dates_data = [];

            $regular_date_types = FleximportConfig::get("HISINONE_REGULAR_DATETYPES");
            $regular_date_types = preg_split("/[\s*\n\s*]/", $regular_date_types, -1, PREG_SPLIT_NO_EMPTY);

            $regular_date_types_2 = [];
            foreach ($regular_date_types as $date_type) {
                $date_type = explode("=", $date_type, 1);
                $regular_date_types_2[$date_type[0]] = $date_type[1];
            }
            $regular_date_types = $regular_date_types_2;

            foreach ($data->course as $number => $coursedata) {
                $courses[$number][] = $coursedata->orgunits && $coursedata->orgunits->orgunitLid
                    ? $coursedata->orgunits->orgunitLid[0]
                    : "";
                $lids = $coursedata->orgunits && $coursedata->orgunits->orgunitLid
                    ? implode("|", (array) $coursedata->orgunits->orgunitLid)
                    : "";

                $courses[$number][] = $lids;

                $teacher_usernames = [];
                $teacher_ids = [];

                $personResponsibles = (array) $coursedata->personResponsibles->personResponsible;
                usort($personResponsibles, function ($a, $b) {
                    return $a->sortorder < $b->sortorder
                        ? 1
                        : ($a->sortorder == $b->sortorder ? 0 : -1);
                });
                $coursedata->personResponsibles->personResponsible = $personResponsibles;

                foreach ((array) $coursedata->personResponsibles->personResponsible as $person) {
                    $teacher_ids[] = $person->person->id;
                    $teacher_usernames[] = $person->person->account->username;
                }

                //seminar_cycle_dates
                //var_dump($coursedata); die();

                foreach ((array) $coursedata->plannedDates->plannedDate as $datedata) {

                    if (in_array($datedata->rhythm->id, array_keys($regular_date_types))) {

                        //regelmäßige Termine in Stud.IP
                        $regular_date = [
                            $coursedata->id,
                            $coursedata->defaulttext,
                            $datedata->plannedDatesId,
                            $datedata->rhythm->id,
                            $datedata->rhythm->key,
                            $datedata->rhythm->label,
                            $datedata->rhythm->kiskeyId,
                            $datedata->weekday->id,
                            $datedata->weekday->key,
                            $datedata->weekday->label,
                            $datedata->weekday->kiskeyId,
                            $datedata->firstAppointment,
                            $datedata->lastAppointment,
                            $datedata->time->start,
                            $datedata->time->end,
                            $datedata->time->academicTimeSpecification,
                            $datedata->expectedAttendees,
                            $datedata->notice,
                            $datedata->room->roomId,
                            $datedata->room->room,
                            $datedata->room->roomKey,
                            $datedata->room->floor,
                            $datedata->room->floorKey,
                            $datedata->room->building,
                            $datedata->room->buildingKey,
                            $datedata->room->campus,
                            $datedata->room->campusKey
                        ];
                        //Ausfalltermine in einer Spalte
                        $exdates = [];
                        foreach ((array) $datedata->appointmentCancellations->appointmentCancellation as $cancellation) {
                            $exdates[] = $cancellation->date;
                        }

                        foreach ((array) $datedata->personResponsibles->personResponsible as $person_data) {

                            if (!in_array($person_data->person->account->username, $teacher_usernames)) {
                                $teacher_usernames[] = $person_data->person->account->username;
                            }
                            if (!in_array($person_data->person->id, $teacher_ids)) {
                                $teacher_ids[] = $person_data->person->id;
                            }
                        }

                        $regular_date[] = implode("|", $exdates);

                        $regular_dates[] = $regular_date;

                        //Terminänderungen:
                        /*foreach ((array) $datedata->individualDates->individualDate as $individualDate) {
                            $individual_dates_data[] = [
                                $coursedata->id,
                                $coursedata->defaulttext,
                                $individualDate->id,
                                $datedata->plannedDatesId,
                                $individualDate->date,
                                $individualDate->remark,
                                $individualDate->from,
                                $individualDate->to,
                                $individualDate->weekdayId,
                                $individualDate->roomId,
                                $datedata->room->roomId,
                                $datedata->room->room,
                                $datedata->room->roomKey,
                                $datedata->room->floor,
                                $datedata->room->floorKey,
                                $datedata->room->building,
                                $datedata->room->buildingKey,
                                $datedata->room->campus,
                                $datedata->room->campusKey,
                                $individualDate->date . " " . $individualDate->from,
                                $individualDate->date . " " . $individualDate->to,
                                $coursedata->id
                            ];
                        }*/
                        /*foreach ((array) $datedata->appointmentModifications->appointmentModification as $modification) {
                            $begin = strtotime($modification->initialDate ." " . $modification->initialStart);
                            $end = strtotime($modification->initialDate ." " . $modification->initialEnd);
                            if ($modification->room) {

                            }
                        }*/

                    } else {
                        //unregelmäßige Termine in Stud.IP

                        foreach ((array) $datedata->individualDates->individualDate as $individualDate) {

                            $individual_dates_data[] = [
                                $coursedata->id,
                                $coursedata->defaulttext,
                                $individualDate->id,
                                "",
                                $individualDate->date,
                                $individualDate->remark,
                                $individualDate->from,
                                $individualDate->to,
                                $individualDate->weekdayId,
                                $individualDate->roomId,
                                $datedata->room->roomId,
                                $datedata->room->room,
                                $datedata->room->roomKey,
                                $datedata->room->floor,
                                $datedata->room->floorKey,
                                $datedata->room->building,
                                $datedata->room->buildingKey,
                                $datedata->room->campus,
                                $datedata->room->campusKey,
                                $individualDate->date . " " . $individualDate->from,
                                $individualDate->date . " " . $individualDate->to,
                                $coursedata->id
                            ];
                        }

                    }
                }
                $courses[$number][] = implode("|", $teacher_usernames);
                $courses[$number][] = implode("|", $teacher_ids);
            }

            $this->table->createTable(
                $fields,
                $courses
            );

            $regular_dates_table = FleximportTable::findOneBySQL("process_id = ? AND name = ?", [
                $this->table['process_id'],
                "fleximport_hisinone_z_regulardates"
            ]);
            if ($regular_dates_table) {
                $regular_dates_table->createTable(
                    $regular_dates_fields,
                    $regular_dates
                );
            }

            $individual_dates_table = FleximportTable::findOneBySQL("process_id = ? AND name = ?", [
                $this->table['process_id'],
                "fleximport_hisinone_z_individualdates"
            ]);
            if ($individual_dates_table) {
                $individual_dates_table->createTable(
                    $individual_dates_fields,
                    $individual_dates_data
                );
            }

        } else {
            PageLayout::postError(_("Konnte Daten nicht abrufen."));
        }
    }

    protected function getCoursesData($termkey)
    {
        $limit = 100;
        $offset = 0;
        $data = new stdClass();
        $data->course = [];
        $soap = \HisInOne\Soap::get();
        do {

            $response = $soap->__soapCall("findCoursesOfTerm", [
                ['termKey' => $termkey,
                 'limit'   => $limit,
                 'offset'  => $offset]
            ]);
        if (is_a($response, "SoapFault")) {
            PageLayout::postError("[findCoursesOfTerm termKey=" . $termkey . ", limit=" . $limit . ", offset=" . $offset . "] " . $response->getMessage());
            return false;
        }

        if (isset($response->findCoursesOfTermResponse->course)) {
            if (is_array($response->findCoursesOfTermResponse->course)) {
                $data->course = array_merge($data->course, $response->findCoursesOfTermResponse->course);
            } else {
                $data->course[] = $response->findCoursesOfTermResponse->course;
            }
        }
        $offset += $limit;
    } while (isset($response->findCoursesOfTermResponse->course));
        return $data;
    }


public function getDescription()
    {
        return "Holt sich die Veranstaltungsdaten aus HisInOne.";
    }
}

