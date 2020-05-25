<?php

require_once __DIR__."/HisInOne/Soap.php";
require_once __DIR__."/HisInOne/SoapClient.php";

class fleximport_hisinone_d_students extends FleximportPlugin
{

    public function customImportEnabled()
    {
        return true;
    }

    public function fetchData()
    {
        $soap = \HisInOne\Soap::get();
        $response = $soap->__soapCall("findActiveStudents", array(
            array('termKey' => 20202) //1 = summer, 2 = winter, make for processconfig?
        ));
        $fields = [
            'registrationnumber',
            'firstname',
            'surname',
            'dateofbirth',
            'gender',
            'birthcity',
            'country_id',
            'country_key',
            'country_label',
            'country_hiskeyid',
            'nationality_id',
            'nationality_key',
            'nationality_label',
            'nationality_hiskeyid',
            'enrollmentdate',
            'studystatus_id',
            'studystatus_key',
            'studystatus_label',
            'studystatus_hiskeyid',
            'term',
            'disenrollmentDate',
            'account_username',
            'account_validfrom',
            'account_validto',
            'degreePrograms'
        ];
        $students = [];
        foreach ((array) $response->findActiveStudentsResponse->student as $student_data) {
            $students[] = $this->mapStudentData($student_data);
        }
        $this->table->createTable($fields, $students);
    }

    protected function mapStudentData($data)
    {
        $mapped = [
            $data->registrationnumber,
            $data->firstname,
            $data->surname,
            $data->dateofbirth,
            $data->gender,
            $data->birthcity,
            $data->country->id,
            $data->country->key,
            $data->country->label,
            $data->country->hiskeyId,
            $data->nationality->id,
            $data->nationality->key,
            $data->nationality->label,
            $data->nationality->hiskeyId,
            $data->enrollmentdate,
            $data->studystatus->id,
            $data->studystatus->key,
            $data->studystatus->label,
            $data->studystatus->hiskeyId,
            $data->term,
            $data->disenrollmentDate,
            $data->account->username,
            $data->account->validFrom,
            $data->account->validTo
        ];

        $degreePrograms = [];
        foreach ((array) $data->degreePrograms->degreeProgram as $studyarea) {
            $degreePrograms[] = $studyarea->courseOfStudyId." ".(int) $studyarea->studysemester;
        }
        $mapped[] = implode("|", $degreePrograms);

        return $mapped;
    }

    public function getDescription()
    {
        return "Holt sich die Studierendendaten aus HisInOne.";
    }
}

