<?php

class FleximportUserDomainsDynamic extends FleximportDynamic
{

    public function forClassFields()
    {
        return array(
            'User' => array("fleximport_userdomains" => _("IDs oder Namen der Nutzerdomänen"))
        );
    }

    public function isMultiple()
    {
        return true;
    }

    public function applyValue($object, $value, $line, $sync)
    {
        $olddomains = UserDomain::getUserDomainsForUser($object->getId());
        $olddomain_ids = array_map(function ($d) { return $d->getID(); }, $olddomains);

        if ($sync) {
            foreach ($olddomains as $olddomain) {
                if (!in_array($olddomain->getID(), (array) $value)) {
                    $olddomain->removeUser($object->getId());
                }
            }
        }
        foreach ($value as $userdomain) {
            $domain = new UserDomain($userdomain);
            $domain->addUser($object->getId());
        }
        AutoInsert::instance()->saveUser($object->getId());

        foreach ($value as $domain_id) {
            if (!in_array($domain_id, $olddomain_ids)) {

                $welcome = FleximportConfig::get("USERDOMAIN_WELCOME_" . $domain_id);
                if ($welcome) {
                    $welcome = FleximportConfig::template($welcome, $object->toArray(), $line);
                    if (strpos($welcome, "\n") === false) {
                        $subject = _("Willkommen!");
                    } else {
                        $subject = strstr($welcome, "\n", true);
                        $welcome = substr($welcome, strpos($welcome, "\n") + 1);
                    }
                    $messaging = new messaging();
                    $count = $messaging->insert_message(
                        $welcome,
                        $object->username,
                        '____%system%____',
                        null,
                        null,
                        null,
                        null,
                        $subject,
                        true,
                        'normal'
                    );
                }
            }
        }
    }

    public function currentValue($object, $field, $sync)
    {
        $domain_ids = array();
        foreach (UserDomain::getUserDomainsForUser($object->getId()) as $domain) {
            $domain_ids[] = $domain->getID();
        }
        return $domain_ids;
    }
}
