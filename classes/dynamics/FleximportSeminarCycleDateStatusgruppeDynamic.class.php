<?php

class FleximportSeminarCycleDateStatusgruppeDynamic extends FleximportDynamic
{

    public function forClassFields()
    {
        return array(
            'SeminarCycleDate' => array("fleximport_statusgruppe_id" => _("Gruppenzugehörigkeit"))
        );
    }

    public function isMultiple()
    {
        return true;
    }

    public function applyValue($object, $value, $line, $sync)
    {
        foreach ($object->dates as $date) {
            if ($sync) {
                foreach ($date->statusgruppen as $statusgruppe) {
                    if (!in_array($statusgruppe->getId(), $value)) {
                        $statement = DBManager::get()->prepare("
                            DELETE FROM termin_related_groups
                            WHERE termin_id = :termin_id
                                AND statusgruppe_id = :statusgruppe_id
                        ");
                        $statement->execute([
                            'termin_id' => $date->getId(),
                            'statusgruppe_id' => $statusgruppe->getId()
                        ]);
                    }
                }
            }
            foreach ($value as $statusgruppe_id) {
                $exists = false;
                foreach ($date->statusgruppen as $statusgruppe) {
                    if ($statusgruppe_id === $statusgruppe->getId()) {
                        $exists = true;
                    }
                }
                if (!$exists && Statusgruppen::exists($statusgruppe_id)) {
                    $statement = DBManager::get()->prepare("
                        INSERT IGNORE INTO termin_related_groups
                        SET termin_id = :termin_id,
                            statusgruppe_id = :statusgruppe_id
                    ");
                    $statement->execute([
                        'termin_id' => $date->getId(),
                        'statusgruppe_id' => $statusgruppe_id
                    ]);
                }
            }
        }
    }

    public function currentValue($object, $field, $sync)
    {
        $statusgruppe_ids = null;
        foreach ($object->dates as $date) {
            $statusgruppen = [];
            foreach ($date->statusgruppen as $statusgruppe) {
                $statusgruppen[] = $statusgruppe->getId();
            }
            sort($statusgruppen);
            if ($statusgruppe_ids === null) {
                $statusgruppe_ids = $statusgruppen;
            } elseif (implode("_", $statusgruppe_ids) !== implode("_", $statusgruppen)) {
                //verschiedene Gruppen in der Terminserie
                return [];
            }
        }
        return $statusgruppe_ids;
    }
}
