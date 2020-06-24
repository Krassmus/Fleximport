<?php

class FleximportSeminarCycleDateExdatesDynamic implements FleximportDynamic {

    public function forClassFields()
    {
        return array(
            'SeminarCycleDate' => array("fleximport_exdates" => _("Ausfalltermine"))
        );
    }

    public function isMultiple()
    {
        return true;
    }

    public function applyValue($object, $value, $line, $sync)
    {
        foreach ($value as $key => $mixeddate) {
            if (!is_numeric($mixeddate)) {
                $value[$key] = strtotime($mixeddate);
            }
        }
        $isodates = array_map(function ($v) {
            return date("Y-m-d", $v);
        }, $value);
        foreach ($isodates as $key => $isodate) {
            foreach ($object->dates as $date) {
                if (date("Y-m-d", $date['date']) == $isodate) {
                    $date->cancelDate();
                    unset($value[$key]);
                }
            }
        }
        if ($sync) {
            foreach ($object->exdates as $exdate) {
                $stillcancelled = false;
                $isodate = date("Y-m-d", $exdate['date']);
                foreach ($isodates as $key => $isodate_ausfall) {
                    if ($isodate == $isodate_ausfall) {
                        $stillcancelled = true;
                        break;
                    }
                }
                if (!$stillcancelled) {
                    $exdate->unCancelDate();
                }
            }
        }
    }

    public function currentValue($object, $field, $sync)
    {
        $exdates = [];
        foreach ($object->exdates as $exdate) {
            $exdates[] = date("Y-m-d", $exdate['date']);
        }
        sort($exdates);
        return $exdates;
    }
}
