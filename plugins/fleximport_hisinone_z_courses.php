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

                usort($coursedata->personResponsibles->personResponsible, function ($a, $b) {
                    return $a->sortorder < $b->sortorder
                        ? 1
                        : ($a->sortorder == $b->sortorder ? 0 : -1);
                });

                foreach ((array) $coursedata->personResponsibles->personResponsible as $person) {
                    $teacher_ids[] = $person->person->id;
                    $teacher_usernames[] = $person->person->account->username;
                }

                //seminar_cycle_dates
                foreach ((array) $coursedata->plannedDates->plannedDate as $datedata) {
                    if (in_array($datedata->rhythm->id, [1, 2, 6, 9])) { //1 könnte falsch sein

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
                    } else {
                        //unregelmäßige Termine in Stud.IP
                    }
                }
                $courses[$number][] = implode("|", $teacher_usernames);
                $courses[$number][] = implode("|", $teacher_ids);
            }

            $this->table->createTable($fields, $courses);

            $regular_dates_table = FleximportTable::findOneBySQL("name = ?", ["fleximport_hisinone_z_regulardates"]);
            if ($regular_dates_table) {
                $regular_dates_table->createTable($regular_dates_fields, $regular_dates);
            }

        } else {
            PageLayout::postError(_("Konnte Daten nicht abrufen."));
        }
    }

    protected function getCoursesData($termkey)
    {
        $soap = \HisInOne\Soap::get();
        $response = $soap->__soapCall("findCoursesOfTerm", array(
            array('termKey' => $termkey)
        ));
        if (is_a($response, "SoapFault")) {
            echo $soap->__getLastRequest();
            echo "<br><br>\n\n";
            var_dump($response);
            die();
        }

        return $response->findCoursesOfTermResponse;
    }

    public function getDescription()
    {
        return "Holt sich die Veranstaltungsdaten aus HisInOne.";
    }
}

