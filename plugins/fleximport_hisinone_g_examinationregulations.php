<?php

require_once __DIR__."/HisInOne/Soap.php";
require_once __DIR__."/HisInOne/SoapClient.php";
require_once __DIR__."/HisInOne/DataMapper.php";

class fleximport_hisinone_g_examinationregulations extends FleximportPlugin
{

    public function customImportEnabled()
    {
        return true;
    }

    public function fetchData()
    {
        $soap = \HisInOne\Soap::get();
        $response = $soap->__soapCall("readExaminationRegulations", [
            array('unitId' => 4)
        ]);
        if (is_a($response, "SoapFault")) {
            PageLayout::postError("[readExaminationRegulations] ".$response->getMessage());
            return false;
        }
        list($fields, $data) = \HisInOne\DataMapper::getData($response->examinationRegulationsResponse->cos);

        $this->table->createTable($fields, $data);
    }

    public function getDescription()
    {
        return "Holt sich die Studienbereiche aus HisInOne.";
    }
}

