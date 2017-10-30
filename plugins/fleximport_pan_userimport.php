<?php

class fleximport_pan_userimport extends FleximportPlugin {

    protected $old_domains = null;

    public function beforeUpdate(SimpleORMap $object, $line, $mappeddata)
    {
        $this->old_domains = $object->isNew()
            ? array()
            : UserDomain::getUserDomainsForUser($object->getId());
    }

    public function afterUpdate(SimpleORMap $object, $line)
    {
        $old_domains = array_map(function ($domain) { return $domain->getID(); }, $this->old_domains);
        $new_domains = UserDomain::getUserDomainsForUser($object->getId());
        foreach ($new_domains as $domain) {
            $domain_id = $domain->getId();
            if (!in_array($domain_id, $old_domains)) {
                if ($domain_id === "alumni") {
                    if (count($new_domains) == 1) {
                        $statement = DBManager::get()->prepare("
                            SELECT seminar_user.Seminar_id 
                            FROM seminar_user
                                LEFT JOIN seminar_userdomains ON (seminar_user.Seminar_id = seminar_userdomains.seminar_id)
                            WHERE seminar_user.user_id = :user_id
                                AND seminar_user.Seminar_id NOT IN (SELECT seminar_id FROM seminar_userdomains WHERE userdomain_id = 'alumni')
                        ");
                        $statement->execute(array(
                            'user_id' => $object->getId()
                        ));
                        foreach ($statement->fetchAll(PDO::FETCH_COLUMN, 0) as $seminar_id) {
                            $seminar = new Seminar($seminar_id);
                            $seminar->deleteMember($object->getId());
                        }
                    }
                    $datafield = DataField::findOneBySQL("name = 'Ich will weiterhin als Alumni in Stud.IP geführt werden' AND object_type = 'user'");
                    $user_wants_to_stay = DatafieldEntryModel::findOneBySQL("datafield_id = ? AND range_id = ?", array($datafield->getId(), $object->getId()));
                    if ($user_wants_to_stay && $user_wants_to_stay['content']) {
                        //In Veranstaltung ALUMNI die Statusgruppe anlegen:
                        $datafield = DataField::findOneBySQL("name = 'Alumni' AND object_type = 'user'");
                        if ($datafield) {
                            $entry = DatafieldEntryModel::findOneBySQL("datafield_id = ? AND range_id = ?", array(
                                $datafield->getId(),
                                $object->getId()
                            ));
                            $course = Course::findOneByName("ALUMNI");
                            $gruppenname = $entry ? $entry['content'] : null;
                            if ($course && $gruppenname) {
                                $statusgruppe = Statusgruppen::findOneBySQL("name = ? range_id = ?", array(
                                    $gruppenname,
                                    $course->getId()
                                ));
                                if (!$statusgruppe) {
                                    $statusgruppe = new Statusgruppen();
                                    $statusgruppe['name'] = $gruppenname;
                                    $statusgruppe['range_id'] = $course->getId();
                                    $statusgruppe->store();
                                }
                                if (!$statusgruppe->isMember($object->getId())) {
                                    $statusgruppe->addUser($object->getId());
                                }
                            }
                        }
                    } else {
                        $object->delete();
                        $deleted = true;
                    }
                }
            }
        }
    }

    public function getDescription()
    {
        return "Es werden alle neuen Alumni in der Veranstaltung ALUMNI in die entsprechende Statusgruppe eingetragen. " .
            "Falls die Studenten in die Domäne 'alumni' neu eingetragen werden sollen, aber nicht einverstanden sind, als Alumni weiter geführt zu werden, werden die Nutzer gelöscht, " .
            "anstatt in die Alumni-Domäne übertragen zu werden.";
    }
}

