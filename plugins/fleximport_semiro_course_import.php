<?php

class fleximport_semiro_course_import extends FleximportPlugin {

    private $new_dozenten = array();

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
            "SEMIRO_DILP_KENNUNG_FIELD"
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
        $doc->loadXML(studip_utf8decode($result->return));
        $seminar_data = array();
        foreach ($doc->getElementsByTagName("seminar") as $seminar) {
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

    public function beforeUpdate($object, $line, $mappeddata)
    {
        foreach ($mappeddata['fleximport_dozenten'] as $dozent_id) {
            if ($object->isNew()) {
                $this->new_dozenten[] = $dozent_id;
            } else {
                $coursemember = CourseMember::find(array($object->getId(), $dozent_id));
                if (!$coursemember || ($coursemember['status'] !== "dozent")) {
                    $this->new_dozenten[] = $dozent_id;
                }
            }
        }
    }

    public function afterUpdate($object, $line)
    {
        $messaging = new messaging();
        //Email an Dozenten:
        foreach ((array) $this->new_dozenten as $user_id) {
            $message = sprintf(_('Sie wurden von Semiro als DozentIn in die Veranstaltung **%s** eingetragen.'), $object->name);
            $messaging->insert_message(
                $message,
                get_username($user_id),
                '____%system%____',
                FALSE,
                FALSE,
                '1',
                FALSE,
                sprintf('%s %s', _('Systemnachricht:'), _('Eintragung in Veranstaltung')),
                TRUE
            );
        }


        $teilnehmergruppe = $line['teilnehmergruppe'];
        if ($teilnehmergruppe) {
            $seminar = new Seminar($object->getId());
            $datafield = Datafield::findOneByName(FleximportConfig::get("SEMIRO_USER_DATAFIELD_NAME"));
            $dilp_kennung_feld = FleximportConfig::get("SEMIRO_DILP_KENNUNG_FIELD");
            if (!$dilp_kennung_feld) {
                $dilp_kennung_feld = "dilp_teilnehmer";
            }
            if ($datafield) {
                $statement = DBManager::get()->prepare("
                    SELECT `".addslashes($dilp_kennung_feld)."`
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
                        $was_member = CourseMember::findOneBySQL("seminar_id = ? AND user_id = ?", array(
                            $object->getId(),
                            $entry['range_id']
                        ));
                        $seminar->addMember($entry['range_id']);
                        if (!$was_member) {
                            $message = sprintf(_('Sie wurden von Semiro als TeilnehmerIn in die Veranstaltung **%s** eingetragen.'), $seminar->name);
                            $messaging->insert_message(
                                $message,
                                get_username($entry['range_id']),
                                '____%system%____',
                                FALSE,
                                FALSE,
                                '1',
                                FALSE,
                                sprintf('%s %s', _('Systemnachricht:'), _('Eintragung in Veranstaltung')),
                                TRUE
                            );
                        }
                        //Zu Statusgruppe hinzufügen:
                        $gruppe = Statusgruppen::findOneBySQL("range_id = ? AND name = ?", array(
                            $object->getId(),
                            $teilnehmergruppe
                        ));
                        if (!$gruppe) {
                            $gruppe = new Statusgruppen();
                            $gruppe['range_id'] = $object->getId();
                            $gruppe['name'] = $teilnehmergruppe;
                            $gruppe->store();
                        }
                        if (!$gruppe->isMember($entry['range_id'])) {
                            $gruppe->addUser($entry['range_id']);
                        }
                        $gruppe->updateFolder(true);
                    }
                }
            }
        }
    }



}