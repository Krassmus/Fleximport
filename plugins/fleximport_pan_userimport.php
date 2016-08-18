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
        foreach ($new_domains as $domain_id) {
            if (!in_array($domain_id, $old_domains)) {
                $welcome = FleximportConfig::get("USERDOMAIN_WELCOME_".$domain_id);
                if ($welcome) {
                    foreach ($object->toArray() as $field => $value) {
                        $welcome = str_replace("{{".$field."}}", $value, $welcome);
                    }
                    foreach ($line as $field => $value) {
                        $welcome = str_replace("{{".$field."}}", $value, $welcome);
                    }
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
                    var_dump($count);
                }
            }
        }
    }
}

