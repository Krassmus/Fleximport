<?php

require_once __DIR__."/HisInOne/Soap.php";
require_once __DIR__."/HisInOne/SoapClient.php";
require_once __DIR__."/HisInOne/DataMapper.php";

class fleximport_hisinone_z_courses extends FleximportPlugin
{

    public function customImportEnabled()
    {
        return true;
    }

    public function fetchData()
    {
        if (!FleximportConfig::get("HISINONE_TERMKEY")) {
            PageLayout::postInfo(_("Es fehlt die Konfiguration HISINONE_TERMKEY."));
            return null;
        }

        $data = $this->getCoursesData((int) FleximportConfig::get("HISINONE_TERMKEY"));
        if ($data) {
            list($fields, $courses) = \HisInOne\DataMapper::getData($data->course);

            $fields[] = "heimatinstitut_lid";
            $fields[] = "institut_lids";
            foreach ($data->course as $number => $coursedata) {
                $courses[$number][] = $coursedata->orgunits && $coursedata->orgunits->orgunitLid
                    ? $coursedata->orgunits->orgunitLid[0]
                    : "";
                $lids = $coursedata->orgunits && $coursedata->orgunits->orgunitLid
                    ? implode("|", (array) $coursedata->orgunits->orgunitLid)
                    : "";
                $courses[$number][] = $lids;
            }

            $this->table->createTable($fields, $courses);
        } else {
            PageLayout::postError(_("Konnte Daten nicht abrufen."));
        }
    }

    protected function getCoursesData($termkey)
    {
        $soap = \HisInOne\Soap::get();
        $response = $soap->__soapCall("findCoursesOfTerm", array(
            array('termKey' => $termkey)
        ));
        if (is_a($response, "SoapFault")) {
            echo $soap->__getLastRequest();
            echo "<br><br>\n\n";
            var_dump($response);
            die();
        }

        return $response->findCoursesOfTermResponse;
    }

    public function getDescription()
    {
        return "Holt sich die Veranstaltungsdaten aus HisInOne.";
    }
}

