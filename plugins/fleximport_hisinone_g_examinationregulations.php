<?php

require_once __DIR__."/HisInOne/Soap.php";
require_once __DIR__."/HisInOne/SoapClient.php";
require_once __DIR__."/HisInOne/DataMapper.php";

class fleximport_hisinone_g_examinationregulations extends FleximportPlugin
{
    private $fl_data  = [];
    private $position = [];
    private $objects  = [];

    public function customImportEnabled()
    {
        return true;
    }

    public function fetchData() {
        $this->cd_soap = \HisInOne\Soap::get('HISINONE_CURRICULUM_DESIGNER_WSDL_URL');
        $soap = \HisInOne\Soap::get();

        foreach ($this->getUnitIds() as $parent_id => $unitids) {
            foreach ($unitids as $unitid) {
                $response = $soap->__soapCall("readExaminationRegulations", [
                    ['unitId' => $unitid]
                ]);
                if (is_a($response, "SoapFault")) {
                    PageLayout::postError("[readExaminationRegulations] ".$response->getMessage());
                    return false;
                }
                $this->getFlattendStructure($response->examinationRegulationsResponse,
                        $response->examinationRegulationsResponse->id);
            }
        }
        $fd = [];
        foreach ($this->fl_data as $k => $v) {
            $fd[$k] = (object) $v;
        }

        list($fields, $data) = \HisInOne\DataMapper::getData($fd);
        $this->table->createTable($fields, $data);
    }

    public function getDescription()
    {
        return "Holt sich die Studiengangteilversionen aus HisInOne.";
    }

    private function getUnitIds()
    {
        $unit_ids = [];
        $studycourse_table = FleximportTable::findOneBySQL('name = ?', ['fleximport_hisinone_f_studycourses']);
        foreach ($studycourse_table->getLines() as $line) {
            if ($line['unitid']) {
                $unit_ids[$line['id']] = explode('|', $line['unitid']);
            }
        }
        return $unit_ids;
    }

    private function getFlattendStructure($exreg, $id = null, $parent_id = null, $parent_key = null)
    {
        if (!$parent_key) {
            $id = $exreg->id;
        }

        $parent_id = $parent_id ?: $id;
        if ($exreg->elementtype->key == 'M') {
            $this->getModuleMetaData($exreg);
        }
        foreach ((array) $exreg as $field => $value) {

            $field = strtolower($parent_key ? $parent_key . '__' . $field : $field);
            $method = 'set_' . $field;
            if ($field === 'id') {
                $this->fl_data[$id . '_' . $parent_id]['parent_id'] = $parent_id;
            }
            if (method_exists($this, $method)) {
                $this->$method($id, $value, $parent_id);
            } elseif (is_object($value)) {
                $this->getFlattendStructure($value, $id, $parent_id, $field);
            } else {
                $this->fl_data[$id . '_' . $parent_id][$field] = $value;
            }
        }
        $this->objects[$id] = $this->fl_data[$id . '_' . $parent_id];
    }

    private function getModuleMetaData(&$unit)
    {
        if ($unit->id) {
            $response = $this->cd_soap->__soapCall('readUnit', [
                ['unitId' => $unit->id]
            ]);
            $unit->unitAttributes = $response->unitAttributes;
        }
    }

    private function set_erchildren($id, $value, $parent_id)
    {
        foreach ((array) $value->examinationRegulationsResponse as $child) {
            $this->getFlattendStructure($child, $child->id, $id);
        }
    }

    private function set_validfrom($id, $value, $parent_id)
    {
        $this->fl_data[$id . '_' . $parent_id]['start_sem'] = '';
        $uts = strtotime($value);
        $sem = Semester::findByTimestamp($uts);
        if ($sem) {
            $this->fl_data[$id . '_' . $parent_id]['start_sem'] = $sem->id;
        }
        $this->fl_data[$id . '_' . $parent_id]['validfrom'] = $value;
    }

    private function set_validto($id, $value, $parent_id)
    {
        $this->fl_data[$id . '_' . $parent_id]['end_sem'] = '';
        $uts = strtotime($value);
        $sem = Semester::findByTimestamp($uts);
        if ($sem) {
            $this->fl_data[$id . '_' . $parent_id]['end_sem'] = $sem->id;
        }
        $this->fl_data[$id . '_' . $parent_id]['validto'] = $value;
    }

    private function set_elementtype__key($id, $value, $parent_id)
    {
        $data_id = $id . '_' . $parent_id;
        $this->fl_data[$data_id]['elementtype__key'] = $value;
        $this->fl_data[$data_id]['parent_elementtype__key'] = '';
        $parent_data_id = $this->fl_data[$data_id]['parent_id'];
        if ($id != $parent_id && $this->objects[$parent_id]['elementtype__key']) {
            $this->fl_data[$data_id]['parent_elementtype__key'] = $this->objects[$parent_id]['elementtype__key'];
            $this->fl_data[$data_id]['studip_position'] = $this->position[$parent_data_id][$value]++;
        }
    }

}

