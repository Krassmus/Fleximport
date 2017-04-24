<?php

class fleximport_semiro_groupmember extends FleximportPlugin {

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
        return "Mapped das Feld statusgruppe_id.";
    }
}

