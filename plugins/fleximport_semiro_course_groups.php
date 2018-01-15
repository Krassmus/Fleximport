<?php

class fleximport_semiro_course_groups extends FleximportPlugin {

    public function customImportEnabled()
    {
        return true;
    }

    public function neededConfigs()
    {
        return array(
            "SEMIRO_SOAP_COURSE_WSDL",
            "SEMIRO_SOAP_PASSWORD",
            "SEMIRO_USER_DATAFIELD_NAME",
            "SEMIRO_DILP_KENNUNG_FIELD",
            "SEMIRO_SEND_MESSAGES"
        );
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

        $fields = array();

        $doc = new DOMDocument();
        $doc->loadXML($result->return);
        $seminar_data = array();
        foreach ($doc->getElementsByTagName("seminar") as $seminar) {
            $seminar_data_row = array();
            foreach ($seminar->childNodes as $attribute) {
                if ($attribute->tagName) {
                    if (!in_array(trim($attribute->tagName), $fields)) {
                        $fields[] = trim($attribute->tagName);
                    }
                    $seminar_data_row[] = trim($attribute->nodeValue);
                }
            }
            $seminar_data[] = $seminar_data_row;
        }
        $this->table->createTable($fields, $seminar_data);
    }

    public function fieldsToBeMapped()
    {
        return array(
            "statusgruppe_id"
        );
    }

    /**
     * @param string $field: name of the field of target table (not the imported table!)
     * @param array $line : all other data from that line.
     * @return mixed: if no mapping should apply map to false. null maps
     * to database NULL. Any other value will map to a string value.
     */
    public function mapField($field, $line) {
        if ($field === "statusgruppe_id") {
            $course = Course::findOneBySQL("name = ?", array($line['name_veranstaltung']));
            if ($course) {
                $statusgruppe = Statusgruppen::findOneBySQL("name = ? AND range_id = ?", array($line['teilnehmergruppe'], $course->getId()));
                if ($statusgruppe) {
                    return $statusgruppe->getId();
                }
            }
        }
        return false;
    }

    public function getDescription() {
        return "Zieht sich die Daten aus Semiro. Zudem wird das Feld statusgruppe_id dynamisch gemapped aus den Feldern `name_veranstaltung` und `teilnehmergruppe`, die beide vorhanden sein sollten.";
    }
}

