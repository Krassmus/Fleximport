<?php

require_once __DIR__."/HisInOne/Soap.php";
require_once __DIR__."/HisInOne/SoapClient.php";

class fleximport_hisinone_a_teachers extends FleximportPlugin
{

    public function customImportEnabled()
    {
        return true;
    }

    public function fetchData()
    {

    }

    protected function getInstituteData($lid = null)
    {
        $soap = \HisInOne\Soap::get();
        $response = $soap->__soapCall("readOrgUnit", array(
            array('lid' => $lid)
        ));
        return $response->orgunitResponse;
    }

    protected function mapInstituteData($data)
    {
        $mapped = [
            $data->id,
            $data->lid,
            $data->parentLid,
            $data->uniquename,
            $data->shorttext,
            $data->defaulttext,
            $data->longtext,
            $data->orgunitType->id,
            $data->orgunitType->key,
            $data->orgunitType->label,
            $data->orgunitType->hiskeyId,
            $data->sortorder,
            $data->validFrom,
            $data->validTo
        ];

        return $mapped;
    }

    protected function mapAffiliationData($data, $affiliation_data)
    {
        $mapped = [
            $data->lid,
            $data->defaulttext ?: "",
            $affiliation_data->validFrom ?: "",
            $affiliation_data->validTo ?: "",
            $affiliation_data->person->id,
            $affiliation_data->person->firstname ?: "",
            $affiliation_data->person->surname ?: "",
            $affiliation_data->person->gender ?: "",
            $affiliation_data->person->birthname ?: "",
            $affiliation_data->person->nameprefix ?: "",
            $affiliation_data->person->namesuffix ?: "",
            $affiliation_data->person->academicdegreesuffix ?: "",
            $affiliation_data->person->academicdegree ? $affiliation_data->person->academicdegree->label : "",
            $affiliation_data->person->title ? $affiliation_data->person->label : "",
            $affiliation_data->person->account->username,
            $affiliation_data->affiliationType->id,
            $affiliation_data->affiliationType->key,
            $affiliation_data->affiliationType->label ?: "",
            $affiliation_data->affiliationType->hiskeyId
        ];

        return $mapped;
    }

    public function getDescription()
    {
        return "Holt sich die Einrichtungsdaten aus HisInOne.";
    }
}

