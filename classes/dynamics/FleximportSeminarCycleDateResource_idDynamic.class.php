<?php

class FleximportSeminarCycleDateResource_idDynamic implements FleximportDynamic {

    public function forClassFields()
    {
        return array(
            'SeminarCycleDate' => array("fleximport_resource_id" => _("Raumbuchung eines regelmäßigen Termins"))
        );
    }

    public function isMultiple()
    {
        return false;
    }

    public function applyValue($object, $value, $line, $sync)
    {
        $this->resource = $value ? Resource::find($value) : null;
        $user = User::findCurrent();
        foreach ($object->dates as $date) {
            if ($this->resource) {
                if ($date->room_booking['resource_id'] != $value) {
                    try {
                        $this->resource->createBooking(
                            $user,
                            $date->getId(),
                            [[
                                'begin' => $date['date'],
                                'end' => $date['end_time']
                            ]],
                            null,
                            0,
                            null,
                            0,
                            '',
                            '',
                            0,
                            false //force_booking
                        );
                    } catch (Exception $e) {
                        if ($date->room_booking) {
                            $date->room_booking->delete();
                        }
                    }
                }
            } else {
                $date->room_booking->delete();
            }
        }
        foreach ($object->exdates as $exdate) {
            $exdate['resource_id'] = $value;
            $exdate->store();
        }
    }

    public function currentValue($object, $field, $sync)
    {
        $resource_ids = [];
        foreach ($object->dates as $dates) {
            if ($dates->room_booking) {
                $resource_ids[$dates->room_booking['resource_id']]++;
            }
        }
        arsort($resource_ids);
        foreach ($resource_ids as $resource_id => $value) {
            return $resource_id;
            break;
        }
        return null;
    }
}
