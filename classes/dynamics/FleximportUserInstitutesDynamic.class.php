<?php

class FleximportUserInstitutesDynamic implements FleximportDynamic {

    public function forClassFields()
    {
        return array(
            'User' => array("fleximport_user_inst" => _("institut_ids"))
        );
    }

    public function isMultiple()
    {
        return true;
    }

    public function applyValue($object, $value, $line, $sync)
    {
        $old_institutes = $this->currentValue($object, "fleximport_user_inst", $sync);
        if ($object['perms'] !== "root") {
            foreach ($value as $institut_id) {
                $member = new InstituteMember(array($object->getId(), $institut_id));
                $member['inst_perms'] = $object['perms'];
                $member->store();
            }
        }
        if ($sync) {
            foreach (array_diff($old_institutes, $value) as $institut_id) {
                $member = InstituteMember::deleteBySQL("institut_id = :institut_id AND user_id = :user_id", array(
                    'institut_id' => $institut_id,
                    'user_id' => $object->getId()
                ));
            }
        }
    }

    public function currentValue($object, $field, $sync)
    {
        $select = DBManager::get()->prepare("
            SELECT institut_id 
            FROM user_inst
            WHERE user_id = ?
        ");
        $select->execute(array($object->getId()));
        return $select->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}