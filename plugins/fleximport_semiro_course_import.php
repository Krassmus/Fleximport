<?php

class fleximport_semiro_course_import extends FleximportPlugin {

    public function customImportEnabled()
    {
        return true;
    }

    /**
     * You can specify a custom import.
     * @return bool
     */
    public function fetchData()
    {
        $wsdl = FleximportConfig::get("SEMIRO_SOAP_COURSE_WSDL");
        $soap = new SoapClient($wsdl, array(
            'trace' => 1,
            'exceptions' => 0,
            'cache_wsdl' => $GLOBALS['CACHING_ENABLE'] || !isset($GLOBALS['CACHING_ENABLE'])
                ? WSDL_CACHE_BOTH
                : WSDL_CACHE_NONE,
            'features' => SOAP_SINGLE_ELEMENT_ARRAYS
        ));
        $file = strtolower(substr($wsdl, strrpos($wsdl, "/") + 1));
        $soapHeaders = new SoapHeader($file, 'Header', array(
            'pw' => FleximportConfig::get("SEMIRO_SOAP_PASSWORD")
        ));
        $soap->__setSoapHeaders($soapHeaders);
        $result = $soap->getSeminareXML(array('pw' => FleximportConfig::get("SEMIRO_SOAP_PASSWORD")));
        if (is_a($result, "SoapFault")) {
            throw new Exception("SOAP-error: " . $result->faultstring);
        }

        $fields = array(
            "ANMELDEMODUS",
            //"ANREDE_DOZENT",
            //"BETEILIGTE_EINRICHTUNGEN",
            //"DAUER",
            "DILP_DOZENT",
            "DOZENT",
            //"EMAIL_DOZENT",
            "ENDTERMIN",
            "HEIMAT_EINRICHTUNG",
            "ID_DOZENT",
            "KATEGORIE",
            //"NACHNAME_DOZENT",
            "NAME_VERANSTALTUNG",
            "SCHULUNGSART",
            "STARTTERMIN",
            "STUDIENBEREICH",
            "TEILNEHMERGRUPPE",
            "TITEL_DOZENT",
            //"TURNUS",
            //"TYP_VERANSTALTUNG",
            "UNTERTITEL",
            "VERANSTALTUNGSNUMMER",
            //"VORNAME_DOZENT"
        );

        $doc = new DOMDocument();
        $doc->loadXML(studip_utf8decode($result->return));
        $seminar_data = array();
        foreach ($doc->getElementsByTagName("seminar") as $seminar) {
            $seminar_data_row = array();
            foreach ($fields as $attribute) {
                foreach ($seminar->getElementsByTagName(strtoupper($attribute)) as $valueNode) {
                    $seminar_data_row[] = studip_utf8decode(trim($valueNode->nodeValue));
                }
            }
            $seminar_data[] = $seminar_data_row;
        }
        $this->table->createTable($fields, $seminar_data);
    }

    public function fieldsToBeMapped()
    {
        return array(
            "fleximport_studyarea"
        );
    }

    /**
     * @param string $field: name of the field of target table (not the imported table!) like
     * @return mixed: if no mapping should apply map to false. null maps
     * to database NULL. Any other value will map to a string value.
     */
    public function mapField($field, $line) {
        if ($field === "fleximport_studyarea") {
            $studienbereiche = array(
                "0" => "Webinare / eLearning",
                "1" => "Studiengebiet 1",
                "2" => "Studiengebiet 2",
                "3" => "Studiengebiet 3",
                "4" => "Studiengebiet 4",
                "5" => "Studiengebiet 5",
                "6" => "Trainingszentrum",
                "8" => "BKA / DHPOL / externe Fobi",
                "9" => "Sonstiges"
            );
            $studyareas = array();
            foreach (StudipStudyArea::findBySQL("name = ?", array($studienbereiche[$line['studienbereich']])) as $study_area) {
                $studyareas[] = $study_area->getId();
            }

            return $studyareas;
        }
        return false;
    }

    public function afterUpdate($object, $line)
    {
        $teilnehmergruppe = $line['teilnehmergruppe'];
        if ($teilnehmergruppe) {
            $seminar = new Seminar($object->getId());
            $datafield = Datafield::findOneByName(FleximportConfig::get("SEMIRO_USER_DATAFIELD_NAME"));
            if ($datafield) {
                $statement = DBManager::get()->prepare("
                    SELECT id_teilnehmer
                    FROM fleximport_semiro_participant_import
                    WHERE teilnehmergruppe = ?
                ");
                $statement->execute(array($teilnehmergruppe));
                $ids = $statement->fetchAll(PDO::FETCH_COLUMN, 0);
                foreach ($ids as $id_teilnehmer) {
                    $entry = DatafieldEntryModel::findOneBySQL("datafield_id = ? AND content = ? ", array(
                        $datafield->getId(),
                        $id_teilnehmer
                    ));
                    if ($entry) {
                        $seminar->addMember($entry['range_id']);
                    }
                }
            }
        }
    }



}