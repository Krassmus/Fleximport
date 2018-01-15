<?php

require_once __DIR__."/FleximportChecker.interface.php";

class FleximportCourseChecker implements FleximportChecker {

    public function check($data, $virtualobject, $relevantfields) {
        $errors = "";
        if (!$data['seminar_id']) {
            if (!$data['fleximport_dozenten'] || !count($data['fleximport_dozenten'])) {
                $errors .= "Dozent kann nicht gemapped werden. ";
            } else {
                $exist = false;
                foreach ((array) $data['fleximport_dozenten'] as $dozent_id) {
                    if (User::find($dozent_id)) {
                        $exist = true;
                        break;
                    }
                }
                if (!$exist) {
                    $errors .= "Angegebene Dozenten sind nicht im System vorhanden. ";
                } else {
                    if ($GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$virtualobject['status']]['class']]['only_inst_user']) {
                        $statement = DBManager::get()->prepare("
                                    SELECT 1
                                    FROM user_inst
                                    WHERE user_id IN (:dozent_ids)
                                        AND Institut_id IN (:institut_ids)
                                        AND inst_perms IN ('autor','tutor','dozent')
                                ");
                        $statement->execute(array(
                            'dozent_ids' => (array) $data['fleximport_dozenten'],
                            'institut_ids' => $data['fleximport_related_institutes'] ?: array($virtualobject['institut_id'])
                        ));
                        if (!$statement->fetch(PDO::FETCH_COLUMN, 0)) {
                            $errors .= "Keiner der Dozenten ist in einer beteiligten Einrichtung angestellt. ";
                        }
                    }
                }
            }
            if (!$data['institut_id'] || !Institute::find($data['institut_id'])) {
                $errors .= "Keine gültige Heimateinrichtung. ";
            }
            if (!Semester::findByTimestamp($data['start_time'])) {
                $errors .= "Semester wurde nicht gefunden. ";
            }
            if ($data['status']) {
                if ($GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$data['status']]['class']]['bereiche']) {
                    $found = false;
                    foreach ((array)$data['fleximport_studyarea'] as $sem_tree_id) {
                        if (StudipStudyArea::find($sem_tree_id)) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $errors .= "Keine (korrekten) Studienbereiche definiert. ";
                    }
                }
            } else {
                $errors .= "Kein Veranstaltungstyp definiert. ";
            }
        }

        return $errors;
    }
}