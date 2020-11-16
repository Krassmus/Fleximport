<?php

require_once __DIR__."/HisInOne/Soap.php";
require_once __DIR__."/HisInOne/SoapClient.php";
require_once __DIR__."/HisInOne/DataMapper.php";

class fleximport_hisinone_f_studycourses extends FleximportPlugin
{

    public function customImportEnabled()
    {
        return true;
    }

    public function fetchData()
    {
        $soap = \HisInOne\Soap::get();
        $response = $soap->__soapCall("findCoursesOfStudy", [
            ['versionDate' => "2020-10-23"] //this date doesn't seem to be relevant
        ]);
        if (is_a($response, "SoapFault")) {
            PageLayout::postError("[findCoursesOfStudy] ".$response->getMessage());
            return false;
        }
        list($fields, $data) = \HisInOne\DataMapper::getData($response->cosResponse->cos);
        $this->table->createTable($fields, $data);
    }

    public function getDescription()
    {
        return "Holt sich die Studienbereiche aus HisInOne.";
    }
}

