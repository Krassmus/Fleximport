<?php

class FleximportSwsMapper implements FleximportMapper
{

    public function getName()
    {
        return "SWS";
    }

    public function possibleFieldnames()
    {
        return array("sws");
    }

    public function possibleFormats()
    {
        $formats = array(
            "seminar_cycle_date" => "Start und Ende eines regelmäßigen Termins"
        );
        return $formats;
    }

    public function map($format, $value, $data, $sormclass)
    {
        switch ($format) {
            case "seminar_cycle_date":
                $start = explode(":", $data['start_time']);
                $end = explode(":", $data['end_time']);
                $sws = round($end[0] - $start[0] + ($end[1] - $start[1]) / 60 + ($end[1] - $start[1]) / 60 / 60, 2);
                return $sws;
                break;
        }
    }

}
