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
            "HISINONE_SOAP_PASSWORD",
            "HISINONE_VIRTUAL_INSTITUT_ROOT_LID"
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
            foreach ((array) $data->affiliations->affiliation as $affiliation_data) {
                $affiliations[] = $this->mapAffiliationData($data, $affiliation_data);
            }
            $fetched_lids[] = $data->lid;
            foreach ((array) $data->children->child as $child) {
                if (!in_array($child->lid, $fetched_lids)) {
                    $lids[] = $child->lid;
                }
            }
            $max = 100;
            while (($max === null || count($fetched_lids) < $max) && (count($lids) > 0)) {
                $lid = array_shift($lids);
                if (!in_array($lid, $fetched_lids)) {
                    $data = $this->getInstituteData($lid);
                    if ($data !== false) {
                        $institutes[] = $this->mapInstituteData($data);
                        foreach ((array) $data->affiliations->affiliation as $affiliation_data) {
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
            }

            $fields = [
                'id',
                'lid',
                'fakultaet_lid',
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

            if (FleximportConfig::get("HISINONE_VIRTUAL_INSTITUT_ROOT_LID")) {
                //remove root institute which we don't need in Stud.IP:
                foreach ($institutes as $key => $institute_data) {
                    if ($institute_data[1] == FleximportConfig::get("HISINONE_VIRTUAL_INSTITUT_ROOT_LID")) {
                        unset($institutes[$key]);
                    }
                }

                //map fakultaet_lid
                $parent_lids = [];
                foreach ($institutes as $key => $institute_data) {
                    $parent_lids[$institute_data[1]] = $institute_data[3];
                }
                do {
                    $workleft = false;
                    foreach ($institutes as $key => $institute_data) {
                        if (($institute_data[2] != FleximportConfig::get("HISINONE_VIRTUAL_INSTITUT_ROOT_LID"))
                            && ($parent_lids[$institute_data[2]] != FleximportConfig::get("HISINONE_VIRTUAL_INSTITUT_ROOT_LID"))) {
                            $institute_data[2] = $parent_lids[$institute_data[2]];
                            $institutes[$key] = $institute_data;
                            if ($parent_lids[$institute_data[2]] != FleximportConfig::get("HISINONE_VIRTUAL_INSTITUT_ROOT_LID")) {
                                $workleft = true;
                            }
                        }
                    }
                } while ($workleft);

                foreach ($institutes as $key => $institute_data) {
                    if ($institute_data[2] == FleximportConfig::get("HISINONE_VIRTUAL_INSTITUT_ROOT_LID")) {
                        $institutes[$key][2] = "fakultaet";
                    }
                }
            }

            $this->table->createTable($fields, $institutes);

            $affiliation_table = "fleximport_hisinone_c_institute_affiliations";
            $aff_table = FleximportTable::findOneBySQL("process_id = ? AND name = ?", [
                $this->table['process_id'],
                $affiliation_table
            ]);
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

    public function fieldsToBeMapped()
    {
        return array(
            "fakultaets_id"
        );
    }

    public function mapField($field, $line)
    {
        $datafield_name = "hio_lid";
        if ($field === "fakultaets_id") {
            $parent_lid = $line['fakultaet_lid'];
            if ($parent_lid == FleximportConfig::get("HISINONE_VIRTUAL_INSTITUT_ROOT_LID")) {
                return "fakultaet"; //means that the institut_id should be written into fakultaet_id like it is usual in Stud.IP
            }
            $fakultaet_id = null;
            if ($parent_lid) {
                $statement = DBManager::get()->prepare("
                    SELECT Institute.Institut_id
                    FROM Institute
                        INNER JOIN datafields_entries ON (datafields_entries.range_id = Institute.Institut_id)
                        INNER JOIN datafields USING (datafield_id)
                    WHERE datafields_entries.content = :lid
                        AND datafields.name = :name
                ");
                $statement->execute([
                    'name' => $datafield_name,
                    'lid' => $parent_lid
                ]);
                $fakultaet_id = $statement->fetch(PDO::FETCH_COLUMN, 0);
            }
            return $fakultaet_id;
        }
        return false;
    }

    protected function getInstituteData($lid = null)
    {
        $soap = \HisInOne\Soap::get();
        $response = $soap->__soapCall("readOrgUnit", array(
            array('lid' => $lid)
        ));
        if (is_a($response, "SoapFault")) {
            PageLayout::postError("[readOrgUnit lid=".$lid."] ".$response->getMessage());
            return false;
        }

        return $response->orgunitResponse;
    }

    protected function mapInstituteData($data)
    {
        $mapped = [
            $data->id,
            $data->lid,
            $data->parentLid,
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

