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
        $limit = 500;
        $soap = \HisInOne\Soap::get();
        $response = $soap->__soapCall("findActiveStudents", array(
            array(
                'termKey' => (int) $this->table->process->getConfig("HISINONE_TERMKEY"), //1 = summer, 2 = winter, make for processconfig?
                'offset' => 0,
                'limit' => $limit
            )
        ));
        if (is_a($response, "SoapFault")) {
            PageLayout::postError("[findActiveStudents termKey=".(int) $this->table->process->getConfig("HISINONE_TERMKEY")."] ".$response->getMessage());
            return false;
        }
        //var_dump($response->findActiveStudentsResponse->students->student); die();
        list($fields, $data) = \HisInOne\DataMapper::getData($response->findActiveStudentsResponse->students->student);
        $fields[] = "user_studiengang";
        foreach ($response->findActiveStudentsResponse->students->student as $index => $student) {
            $data[$index][] = $this->getCourseOfStudy($student);
        }
        $max = $response->findActiveStudentsResponse->countAll;
        $this->table->createTable($fields, $data);

        for ($i = 1; $i * $limit < $max; $i++) {
            $response = $soap->__soapCall("findActiveStudents", array(
                array(
                    'termKey' => (int) $this->table->process->getConfig("HISINONE_TERMKEY"), //1 = summer, 2 = winter, make for processconfig?
                    'offset' => $i * $limit + 1,
                    'limit' => $limit
                )
            ));
            if (is_a($response, "SoapFault")) {
                PageLayout::postError("[findActiveStudents termKey=".(int) $this->table->process->getConfig("HISINONE_TERMKEY")."] ".$response->getMessage());
                return false;
            }
            list($fields, $data) = \HisInOne\DataMapper::getData($response->findActiveStudentsResponse->students->student);
            $fields[] = "degreeprograms_subjectnumber_courseofstudyid_studysemester";
            foreach ($response->findActiveStudentsResponse->students->student as $index => $student) {
                $data[$index][] = $this->getCourseOfStudy($student);
            }
            $this->table->addEntries($fields, $data);
        }
    }

    public function getDescription()
    {
        return "Holt sich die Studierendendaten aus HisInOne.";
    }

    protected function getCourseOfStudy($student)
    {
        $degreeprograms = [];
        foreach ($student->degreePrograms->degreeProgram as $dp_data) {
            //$dp_data->courseOfStudyId ist in der Tabelle  fleximport_hisinone_f_studycourses die Spalte id (anscheinend)

            $fleximport_hisinone_f_studycourses = $this->table->process->getTableByName('fleximport_hisinone_f_studycourses');
            if ($fleximport_hisinone_f_studycourses && $fleximport_hisinone_f_studycourses->isInDatabase()) {
                $courseofstudy_statement = DBManager::get()->prepare("
                        SELECT *
                        FROM `".addslashes($fleximport_hisinone_f_studycourses->getDBName())."`
                        WHERE `id` = :courseofstudyid
                        LIMIT 1
                    ");
                $courseofstudy_statement->execute([
                    'courseofstudyid' => $dp_data->courseOfStudyId
                ]);
                $data = $courseofstudy_statement->fetch(PDO::FETCH_ASSOC);
                if ($data) {
                    $degreeprograms[] = $data['subject__label'] . ": " . $data['degree__label'] . ": " . max(1, floor($dp_data->studysemester));
                } else {
                    $degreeprograms[] = 'Unbestimmbar '.$dp_data->courseOfStudyId;
                }
            } else {
                $degreeprograms[] = $dp_data->courseOfStudyId." ".$dp_data->studysemester;
            }
        }
        return implode("|", $degreeprograms);
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

