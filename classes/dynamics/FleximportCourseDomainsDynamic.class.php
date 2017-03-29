<?php

class FleximportCourseDomainsDynamic implements FleximportDynamic {

    public function forClassFields()
    {
        return array(
            'Course' => array("fleximport_course_userdomains" => _("IDs oder Namen der Nutzerdomänen"))
        );
    }

    public function isMultiple()
    {
        return true;
    }

    public function applyValue($object, $value, $line)
    {
        $statement = DBManager::get()->prepare("
            SELECT userdomain_id
            FROM seminar_userdomains
            WHERE seminar_id = ?
        ");
        $statement->execute(array($object->getId()));
        $olddomains = $statement->fetchAll(PDO::FETCH_COLUMN, 0);
        foreach (array_diff($value, $olddomains) as $to_add) {
            $domain = new UserDomain($to_add);
            $domain->addSeminar($object->getId());
        }
        foreach (array_diff($olddomains, $value) as $to_remove) {
            $domain = new UserDomain($to_remove);
            $domain->removeSeminar($object->getId());
        }
    }

    public function currentValue($object, $field)
    {
        $domain_ids = array();
        foreach (UserDomain::getUserDomainsForSeminar($object->getId()) as $domain) {
            $domain_ids[] = $domain->getID();
        }
        return $domain_ids;
    }
}