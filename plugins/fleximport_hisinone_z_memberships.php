<?php

require_once __DIR__."/HisInOne/Soap.php";
require_once __DIR__."/HisInOne/SoapClient.php";
require_once __DIR__."/HisInOne/DataMapper.php";

class fleximport_hisinone_z_memberships extends FleximportPlugin
{

    public function customImportEnabled()
    {
        return true;
    }

    public function neededProcessConfigs()
    {
        return [
            "HISINONE_TERMKEY",
            "HISINONE_ATTENDEES_WORKSTATUSID"
        ];
    }

    public function fetchData()
    {
        if (!$this->table->process->getConfig("HISINONE_TERMKEY")) {
            PageLayout::postInfo(_("Es fehlt die Konfiguration HISINONE_TERMKEY."));
            return null;
        }

        $fields = [
            'courseId',
            'personId',
            'registrationnumber',
            'cancellation',
            'workstatus__id',
            'workstatus__key',
            'workstatus__label',
            'workstatus__hiskeyId'
        ];
        $memberships = [];

        foreach ($this->table->process->getTableByName("fleximport_hisinone_z_courses")->getLines() as $coursedata) {
            $course_id = $coursedata['id'];
            $data = $this->getMembershipsData($course_id);
            if ($data) {
                foreach ($data as $membership) {
                    $memberships[] = [
                        $course_id,
                        $membership->personId,
                        $membership->registrationnumber,
                        $membership->cancellation,
                        $membership->workstatus->id,
                        $membership->workstatus->key,
                        $membership->workstatus->label,
                        $membership->workstatus->hiskeyId
                    ];
                }
            }
        }

        $this->table->createTable(
            $fields,
            $memberships
        );
    }

    protected function getMembershipsData($course_id)
    {
        $soap = \HisInOne\Soap::get();
        $response = $soap->__soapCall("getAttendeesOfCourse", [[
            'courseId' => $course_id,
            'workstatusId' => FleximportConfig::get("HISINONE_ATTENDEES_WORKSTATUSID")
        ]]);
        if (is_a($response, "SoapFault")) {
            PageLayout::postError("[getAttendeesOfCourse courseId=".$course_id."] ".$response->getMessage());
            return false;
        }

        return $response->getAttendeesOfCourseResponse->attendee;
    }

    public function getDescription()
    {
        return "Holt sich die Veranstaltungsdaten aus HisInOne.";
    }
}

