<?php

class fleximport_semiro_participant_import extends FleximportPlugin {

    public function neededConfigs()
    {
        return array(
            "SEMIRO_SOAP_PARTICIPANTS_WSDL",
            "SEMIRO_SOAP_PASSWORD",
            "SEMIRO_USER_DATAFIELD_NAME",
            "SEMIRO_DILP_KENNUNG_FIELD"
        );
    }

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
        $wsdl = FleximportConfig::get("SEMIRO_SOAP_PARTICIPANTS_WSDL");
        $soap = new SoapClient($wsdl, array(
            'trace' => 1,
            'exceptions' => 0,
            'cache_wsdl' => $GLOBALS['CACHING_ENABLE'] || !isset($GLOBALS['CACHING_ENABLE'])
                ? WSDL_CACHE_BOTH
                : WSDL_CACHE_NONE,
            'features' =>  SOAP_SINGLE_ELEMENT_ARRAYS
        ));
        $file = strtolower(substr($wsdl, strrpos($wsdl, "/") + 1));
        $soapHeaders = new SoapHeader($file, 'Header', array(
            'pw' => FleximportConfig::get("SEMIRO_SOAP_PASSWORD")
        ));
        $soap->__setSoapHeaders($soapHeaders);
        $result = $soap->getTeilnehmerXML(array('pw' => FleximportConfig::get("SEMIRO_SOAP_PASSWORD")));
        if (is_a($result, "SoapFault")) {
            throw new Exception("SOAP-error: ".$result->faultstring);
        }

        $fields = array(
            "EMAIL_TEILNEHMER",
            "ID_TEILNEHMER",
            "TEILNEHMER",
            "TEILNEHMERGRUPPE"
        );

        $fields = array();

        $doc = new DOMDocument();
        $doc->loadXML(studip_utf8decode($result->return));
        $seminar_data = array();
        foreach ($doc->getElementsByTagName("teilnehmer") as $seminar) {
            $seminar_data_row = array();
            foreach ($seminar->childNodes as $attribute) {
                if ($attribute->tagName) {
                    if (!in_array(studip_utf8decode(trim($attribute->tagName)), $fields)) {
                        $fields[] = studip_utf8decode(trim($attribute->tagName));
                    }
                    $seminar_data_row[] = studip_utf8decode(trim($attribute->nodeValue));
                }
            }
            $seminar_data[] = $seminar_data_row;
        }
        $this->table->createTable($fields, $seminar_data);
    }

    public function checkLine($line) {
        $errors = "";

        if (!FleximportTable::findOneByName("fleximport_semiro_course_import")) {
            return "Tabelle fleximport_semiro_course_import existiert nicht. ";
        }

        $dilp_kennung_feld = FleximportConfig::get("SEMIRO_DILP_KENNUNG_FIELD");
        if (!$dilp_kennung_feld) {
            $dilp_kennung_feld = "dilp_teilnehmer";
        }

        if (!$line[$dilp_kennung_feld]) {
            $errors .= "Teilnehmer hat keinen Wert für '$dilp_kennung_feld''. ";
        } else {
            $datafield = Datafield::findOneByName(FleximportConfig::get("SEMIRO_USER_DATAFIELD_NAME"));
            if (!$datafield) {
                $errors .= "System hat kein Datenfeld ".FleximportConfig::get("SEMIRO_USER_DATAFIELD_NAME").", womit die Nutzer identifiziert werden. ";
            } else {
                $entry = DatafieldEntryModel::findOneBySQL("datafield_id = ? AND content = ? ", array(
                    $datafield->getId(),
                    $line[$dilp_kennung_feld]
                ));
                if (!$entry || !User::find($entry['range_id'])) {
                    $errors .= "Nutzer konnte nicht durch id_teilnehmer identifiziert werden. ";
                }
            }
        }

        if (!$line['teilnehmergruppe']) {
            $errors .= "Keine Teilnehmergruppe. ";
        } else {
            $statement = DBManager::get()->prepare("
                SELECT 1
                FROM fleximport_semiro_course_import
                WHERE teilnehmergruppe = ?
            ");
            $statement->execute(array($line['teilnehmergruppe']));
            if (!$statement->fetch()) {
                $errors .= "Nicht verwendete Teilnehmergruppe. ";
            }
        }

        return $errors;
    }



}