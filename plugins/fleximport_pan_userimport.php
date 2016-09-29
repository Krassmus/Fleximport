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
                }
                if ($domain_id === "alumni") {
                    //In Veranstaltung ALUMNI die Statusgruppe anlegen:
                    $datafield = Datafield::findOneBySQL("name = 'Alumni' AND object_type = 'user'");
                    $entry = DatafieldEntry::findOneBySQL("datafield_id = ? AND range_id = ?");
                    $course = Course::findOneByName("ALUMNI");
                    $gruppenname = $entry ? $entry['content'] : null;
                    if ($course && $gruppenname) {
                        $statusgruppe = Statusgruppen::findOneBySQL("name = ? range_id = ?", array($gruppenname, $course->getId()));
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
            }
        }
    }

    public function getDescription()
    {
        return "Den Nutzern werden, wenn sie in eine neue Dom�ne eingetragen werden, Willkommensnachrichten zugesendet. " .
            "Diese sind Konfgurationsvariablen mit dem Namen USERDOMAIN_WELCOME_domainid. Falls es diese Konfigurationsvariablen " .
            "zu der passenden Dom�ne nicht gibt. wird keine Nachricht verschickt. Die erste Zeile in der Konfigurationsvariablen " .
            "ist der Betreff, alle anderen Zeilen die Nachricht selbst. " .
            "Es k�nnen in der Nachricht Variablen der Tabelle oder des User-Objektes verwendet werden mit z.B. {{vorname}} als Schreibweise.";
    }
}

