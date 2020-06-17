<?php

require_once __DIR__."/HisInOne/Soap.php";
require_once __DIR__."/HisInOne/SoapClient.php";
require_once __DIR__."/HisInOne/DataMapper.php";

class fleximport_hisinone_a_institutes extends FleximportPlugin
{

    public function neededConfigs()
    {
        return [
            "HISINONE_SOAP_ENDPOINT",
            "HISINONE_WSDL_URL",
            "HISINONE_SOAP_USERNAME",
            "HISINONE_SOAP_PASSWORD"
        ];
    }

    public function customImportEnabled()
    {
        return true;
    }

    public function fetchData()
    {
        if (!FleximportConfig::get("HISINONE_SOAP_ENDPOINT")) {
            PageLayout::postInfo(_("Es fehlt die Konfiguration HISINONE_SOAP_ENDPOINT."));
        }
        if (!FleximportConfig::get("HISINONE_WSDL_URL")) {
            PageLayout::postInfo(_("Es fehlt die Konfiguration HISINONE_WSDL_URL."));
        }
        $lid = null;

        $fetched_lids = [];
        $lids = [];
        $institutes = [];
        $affiliations = [];
        $data = $this->getInstituteData();
        if ($data) {
            $institutes[] = $this->mapInstituteData($data);
            foreach ($data->affiliations->affiliation as $affiliation_data) {
                $affiliations[] = $this->mapAffiliationData($data, $affiliation_data);
            }
            $fetched_lids[] = $data->lid;
            foreach ($data->children->child as $child) {
                if (!in_array($child->lid, $fetched_lids)) {
                    $lids[] = $child->lid;
                }
            }
            $max = 100000;
            while (($max === null || count($fetched_lids) < $max) && (count($lids) > 0)) {
                $lid = array_shift($lids);
                if (!in_array($lid, $fetched_lids)) {
                    $data = $this->getInstituteData($lid);
                    $institutes[] = $this->mapInstituteData($data);
                    foreach ($data->affiliations->affiliation as $affiliation_data) {
                        $affiliations[] = $this->mapAffiliationData($data, $affiliation_data);
                    }
                    $fetched_lids[] = $data->lid;
                    foreach ((array) $data->children->child as $child) {
                        if (!in_array($child->lid, $fetched_lids)) {
                            $lids[] = $child->lid;
                        }
                    }
                }
            }

            $fields = [
                'id',
                'lid',
                'parentLid',
                'uniquename',
                'shorttext',
                'defaulttext',
                'longtext',
                'orgunittype_id',
                'orgunittype_key',
                'orgunittype_label',
                'orgunittype_hiskeyid',
                'sortorder',
                'validfrom',
                'validTo'
            ];

            $this->table->createTable($fields, $institutes);

            $affiliation_table = "fleximport_hisinone_c_institute_affiliations";
            $aff_table = FleximportTable::findOneBySQL("name = ?", [$affiliation_table]);
            if ($aff_table) {
                $fields = [
                    'institute_lid',
                    'institute_name',
                    'validfrom',
                    'validto',
                    'person_id',
                    'person_firstname',
                    'person_surname',
                    'person_gender',
                    'person_birthname',
                    'person_nameprefix',
                    'person_namesuffix',
                    'person_academicdegreesuffix',
                    'person_academicdegree',
                    'person_title',
                    'person_account_username',
                    'affiliationtype_id',
                    'affiliationtype_key',
                    'affiliationtype_label',
                    'affiliationtype_hiskeyid'
                ];
                $aff_table->createTable($fields, $affiliations);
            }
        } else {
            PageLayout::postError(_("Konnte Daten nicht abrufen."));
        }

    }

    protected function getInstituteData($lid = null)
    {
        $soap = \HisInOne\Soap::get();
        $response = $soap->__soapCall("readOrgUnit", array(
            array('lid' => $lid)
        ));
        if (is_a($response, "SoapFault")) {
            echo $soap->__getLastRequest();
            echo "<br><br>\n\n";
            var_dump($response);
            die();
        }

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

