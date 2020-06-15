<?php

require_once __DIR__."/HisInOne/Soap.php";
require_once __DIR__."/HisInOne/SoapClient.php";
require_once __DIR__."/HisInOne/DataMapper.php";

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
            array('termKey' => (int) FleximportConfig::get("HISINONE_TERMKEY")) //1 = summer, 2 = winter, make for processconfig?
        ));
        list($fields, $data) = \HisInOne\DataMapper::getData($response->findActiveStudentsResponse->student);
        $this->table->createTable($fields, $data);
    }

    public function getDescription()
    {
        return "Holt sich die Studierendendaten aus HisInOne.";
    }

    /*protected function mapStudentData($data)
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
            $data->account->validTo,
            $data->email->adress,
            $data->email->type->id,
            $data->email->type->key,
            $data->email->type->label,
            $data->email->type->hiskeyId
            //TODO degreePrograms
        ];

        $degreePrograms = [];
        foreach ((array) $data->degreePrograms->degreeProgram as $studyarea) {
            $degreePrograms[] = $studyarea->courseOfStudyId." ".(int) $studyarea->studysemester;
        }
        $mapped[] = implode("|", $degreePrograms);

        return $mapped;
    }*/
}

