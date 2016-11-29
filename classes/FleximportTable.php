<?php

class FleximportTable extends SimpleORMap {

    protected $sorm_metadata = array();
    protected $plugin = null;
    protected $already_fetched = false;

    private $user_data = array(); //only needed for welcome email from Stud.IP for new users

    static public function findAll()
    {
        return self::findBySQL("1=1 ORDER BY position ASC, name ASC");
    }

    static protected function configure($config = array())
    {
        $config['db_table'] = 'fleximport_tables';
        if (version_compare($GLOBALS['SOFTWARE_VERSION'], "3.2", ">=")) {
            $config['registered_callbacks']['before_store'][]     = 'cbSerializeData';
            $config['registered_callbacks']['after_store'][]      = 'cbUnserializeData';
            $config['registered_callbacks']['after_delete'][]      = 'cbDeleteTable';
            $config['registered_callbacks']['after_initialize'][] = 'cbUnserializeData';
        }
        parent::configure($config);
    }

    function __construct($id = null)
    {
        if (version_compare($GLOBALS['SOFTWARE_VERSION'], "3.2", "<")) {
            $this->registerCallback('before_store', 'cbSerializeData');
            $this->registerCallback('after_store after_initialize', 'cbUnserializeData');
        }
        parent::__construct($id);
    }

    function cbDeleteTable()
    {
        DBManager::get()->exec("DROP TABLE IF EXISTS `".$this['name']."`;");
        DBManager::get()->exec("DROP VIEW IF EXISTS `".$this['name']."`;");
    }

    function cbSerializeData()
    {
        $this->content['tabledata'] = json_encode(studip_utf8encode($this->content['tabledata']));
        //$this->content_db['tabledata'] = json_encode(studip_utf8encode($this->content_db['tabledata']));
        return true;
    }

    function cbUnserializeData()
    {
        $this->content['tabledata'] = (array) studip_utf8decode(json_decode($this->content['tabledata'], true));
        //$this->content_db['tabledata'] = (array) studip_utf8decode(json_decode($this->content_db['tabledata'], true));
        return true;
    }

    public function isInDatabase()
    {
        //$this->fetchData();

        $statement = DBManager::get()->prepare("
            SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME = :table
                AND TABLE_SCHEMA = :db_name
        ");
        $statement->execute(array(
            'table' => $this['name'],
            'db_name' => $GLOBALS['DB_STUDIP_DATABASE']
        ));
        $is_in_db = $statement->fetch();
        return (bool) $is_in_db;
    }

    public function fetchData()
    {
        if ($this->already_fetched) {
            return;
        }
        $this->already_fetched = true;
        try {
            if (!$this->customImportEnabled()) {
                if (in_array($this['source'], array("csv_upload", "extern"))) {
                    return;
                } elseif ($this['source'] === "database") {
                    $this->fetchDataFromDatabase();
                    return;
                } elseif ($this['source'] === "csv_weblink") {
                    $this->fetchDataFromWeblink();
                    return;
                } elseif($this['source'] === "csv_studipfile") {
                    $output = $this->getCSVDataFromFile(get_upload_file_path($this['tabledata']['weblink']['file_id']), ";");
                    $headline = array_shift($output);
                    $this->createTable($headline, $output);
                    return;
                }
            } else {
                $this->getPlugin()->fetchData();
            }
        } catch (Exception $e) {
            PageLayout::postMessage(MessageBox::error(sprintf(_("Konnte Tabelle '%s' nicht mit Daten befüllen."), $this['name'])));
        }
    }

    public function getExportSecret()
    {
        return md5($this->getId().$GLOBALS['STUDIP_INSTALLATION_ID']);
    }

    public function customImportEnabled()
    {
        $plugin = $this->getPlugin();
        return $plugin && $plugin->customImportEnabled();
    }

    public function getTableHeader()
    {
        $statement = DBManager::get()->prepare("
            SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME = :table_name
                AND TABLE_SCHEMA = :db_name
        ");
        $statement->execute(array(
            'table_name' => $this['name'],
            'db_name' => $GLOBALS['DB_STUDIP_DATABASE']
        ));
        return $statement->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function fetchCount()
    {
        $statement = DBManager::get()->prepare("
            SELECT COUNT(*) FROM `".addslashes($this['name'])."`
        ");
        $statement->execute();
        return $statement->fetch(PDO::FETCH_COLUMN);
    }

    public function fetchLines()
    {
        $statement = DBManager::get()->prepare("
            SELECT * FROM `".addslashes($this['name'])."`
        ");
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchDataFromDatabase()
    {
        switch ($this['tabledata']['server']['type']) {
            case "mssql":
                $extern_db = new PDO(
                    "dblib:host=".$this['tabledata']['server']['adress'].":".$this['tabledata']['server']['port'].";dbname=".$this['tabledata']['server']['dbname']."",
                    $this['tabledata']['server']['user'],
                    $this['tabledata']['server']['password']
                );
                $statement = $extern_db->prepare("
                    SELECT * FROM [".addslashes($this['tabledata']['server']['table'])."]
                ");
                $statement->execute();
                $values = $statement->fetchAll(PDO::FETCH_ASSOC);
                $columns = $extern_db->query("
                    SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ".$extern_db->quote($this['tabledata']['server']['table'])."
                ")->fetchAll(PDO::FETCH_COLUMN, 0);
                break;
            default:
            case "mysql":
                $extern_db = new PDO(
                    "mysql:host=".$this['tabledata']['server']['adress'].":".$this['tabledata']['server']['port'].";dbname=".$this['tabledata']['server']['dbname']."",
                    $this['tabledata']['server']['user'],
                    $this['tabledata']['server']['password']
                );
                $statement = $extern_db->prepare("
                    SELECT * FROM `".addslashes($this['tabledata']['server']['table'])."`
                ");
                $statement->execute();
                $values = $statement->fetchAll(PDO::FETCH_ASSOC);
                $columns = $extern_db->query("
                    SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ".$extern_db->quote($this['tabledata']['server']['table'])."
                ")->fetchAll(PDO::FETCH_COLUMN, 0);
        }

        $output = array();
        foreach ($values as $data) {
            $line = array();
            foreach ($columns as $column) {
                $line[] = $data[$column];
            }
            $output[] = $line;
        }

        $this->createTable($columns, $output);
    }

    public function fetchDataFromWeblink()
    {
        $output = $this->getCSVDataFromURL($this['tabledata']['weblink']['url'], ",");
        $headline = array_shift($output);
        $this->createTable($headline, $output);
    }

    public function createTable($headers, $entries = array())
    {
        $db = DBManager::get();
        $this->drop();
        $create_sql = "CREATE TABLE `".addslashes($this['name'])."` (";
        $create_sql .= "`IMPORT_TABLE_PRIMARY_KEY` BIGINT NOT NULL AUTO_INCREMENT ";
        foreach ($headers as $key => $fieldname) {
            if ($fieldname) {
                $fieldname = strtolower(self::reduceDiakritikaFromIso88591($fieldname));
                $create_sql .= ", ";
                $create_sql .= "`" . addslashes($fieldname) . "` TEXT NOT NULL";
            }
        }
        $create_sql .= ", PRIMARY KEY (`IMPORT_TABLE_PRIMARY_KEY`) ";
        $create_sql .= ") ENGINE=MyISAM";
        $db->exec($create_sql);

        foreach ($entries as $line) {
            $insert_sql = "INSERT INTO `".addslashes($this['name'])."` SET ";
            foreach ($headers as $key => $field) {
                if ($field) {
                    $key < 1 || $insert_sql .= ", ";
                    $value = trim($line[$key]);
                    $field = strtolower(self::reduceDiakritikaFromIso88591($field));
                    $insert_sql .= "`" . addslashes($field) . "` = " . $db->quote($value) . " ";
                }
            }
            $db->exec($insert_sql);
        }
    }

    static public function reduceDiakritikaFromIso88591($text) {
        $text = str_replace(array("ä","Ä","ö","Ö","ü","Ü","ß"), array('ae','Ae','oe','Oe','ue','Ue','ss'), $text);
        $text = str_replace(array('À','Á','Â','Ã','Å','Æ'), 'A' , $text);
        $text = str_replace(array('à','á','â','ã','å','æ'), 'a' , $text);
        $text = str_replace(array('È','É','Ê','Ë'), 'E' , $text);
        $text = str_replace(array('è','é','ê','ë'), 'e' , $text);
        $text = str_replace(array('Ì','Í','Î','Ï'), 'I' , $text);
        $text = str_replace(array('ì','í','î','ï'), 'i' , $text);
        $text = str_replace(array('Ò','Ó','Õ','Ô','Ø'), 'O' , $text);
        $text = str_replace(array('ò','ó','ô','õ','ø'), 'o' , $text);
        $text = str_replace(array('Ù','Ú','Û'), 'U' , $text);
        $text = str_replace(array('ù','ú','û'), 'u' , $text);
        $text = str_replace(array('Ç','ç','Ð','Ñ','Ý','ñ','ý','ÿ'), array('C','c','D','N','Y','n','y','y') , $text);
        return $text;
    }

    public function CSV2Array($content, $delim = ';', $encl = '"', $optional = 1) {
        if ($content[strlen($content)-1]!="\r" && $content[strlen($content)-1]!="\n")
            $content .= "\r\n";

        $reg = '/(('.$encl.')'.($optional?'?(?(2)':'(').
            '[^'.$encl.']*'.$encl.'|[^'.$delim.'\r\n]*))('.$delim.
            '|[\r\n]+)/smi';

        preg_match_all($reg, $content, $treffer);
        $linecount = 0;

        for ($i = 0; $i < count($treffer[3]);$i++) {
            $liste[$linecount][] = str_replace($encl.$encl, $encl, trim($treffer[1][$i],$encl));
            if ($treffer[3][$i] != $delim) $linecount++;
        }
        return $liste;
    }

    public function getCSVDataFromFile($file_path, $delim = ';', $encl = '"', $optional = 1) {
        $contents = file_get_contents($file_path);
        if ($this['tabledata']['source_encoding'] === "utf8") {
            $contents = studip_utf8decode($contents);
        }
        return $this->CSV2Array($contents, $delim, $encl, $optional);
    }

    public function getCSVDataFromURL($file_path, $delim = ';', $encl = '"', $optional = 1) {
        $contents = file_get_contents($file_path);
        if ($this['tabledata']['source_encoding'] === "utf8") {
            $contents = studip_utf8decode($contents);
        }
        return $this->CSV2Array($contents, $delim, $encl, $optional);
    }

    public function drop()
    {
        DBManager::get()->exec("DROP TABLE IF EXISTS `".addslashes($this['name'])."` ");
        DBManager::get()->exec("DROP VIEW IF EXISTS `".addslashes($this['name'])."` ");
    }



    public function doImport()
    {
        if (!$this['import_type']) {
            return array();
        }
        if ($this['import_type'] === "fleximport_mysql_command") {
            $statement = DBManager::get()->prepare($this['tabledata']['fleximport_mysql_command']);
            $statement->execute();
            return array();
        }
        $statement = DBManager::get()->prepare("
            SELECT * FROM `".addslashes($this['name'])."`
        ");
        $statement->execute();
        $protocol = array();
        $item_ids = array();
        $count = 0;
        $count_successful = 0;
        while ($line = $statement->fetch(PDO::FETCH_ASSOC)) {
            $output = $this->importLine($line);
            if (isset($output['errors'])) {
                $error = $output['errors'];
                if ($GLOBALS['FLEXIMPORT_IS_CRONJOB'] && $output['name']) {
                    $error = $output['name'].": ".$error;
                }
                $protocol[] = $error;
            }
            if (isset($output['pk'])) {
                $item_ids[] = is_array($output['pk']) ? implode("-", $output['pk']) : $output['pk'];
            }
            $count++;
            if (!$output['errors']) {
                $count_successful++;
            }
        }
        if ($GLOBALS['FLEXIMPORT_IS_CRONJOB']) {
            echo sprintf(_("%s von %s Datensätze der Tabelle %s erfolgreich importiert."), $count_successful, $count, $this['name'])." \n";
        }
        if ($this['synchronization']) {
            $import_type = $this['import_type'];
            $items = $this->findDeletableItems($item_ids);

            foreach ($items as $item) {
                if (class_exists($import_type)) {
                    $pk = strpos($item['item_id'], "-") !== false
                        ? explode("-", $item['item_id'])
                        : $item['item_id'];
                    $object = new $import_type($pk);
                    $object->delete();
                }
                $item->delete();
            }
            foreach ($item_ids as $item_id) {
                $mapped = FleximportMappedItem::findbyItemId($item_id, $this->getId()) ?: new FleximportMappedItem();
                $mapped['table_id'] = $this->getId();
                $mapped['item_id'] = $item_id;
                $mapped['chdate'] = time();
                $mapped->store();
            }
        }
        return $protocol;
    }

    public function findDeletableItems($not = array()) {
        return FleximportMappedItem::findBySQL(
            "table_id = :table_id AND item_id NOT IN (:ids)",
            array(
                'table_id' => $this->getId(),
                'ids' => $not ?: ""
            )
        );
    }

    public function countDeletableItems($not = array()) {
        return FleximportMappedItem::countBySQL(
            "table_id = :table_id AND item_id NOT IN (:ids)",
            array(
                'table_id' => $this->getId(),
                'ids' => $not ?: ""
            )
        );
    }

    /**
     * Returns the line for the given id as an associative array.
     * @param integer $id
     * @return array : the data of the line.
     */
    public function getLine($id)
    {
        $statement = DBManager::get()->prepare("
            SELECT *
            FROM `".addslashes($this['name'])."`
            WHERE IMPORT_TABLE_PRIMARY_KEY = :id
        ");
        $statement->execute(array('id' => $id));
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Imports a line of the table into the Stud.IP database if the check returns no errors.
     * @param array $line : array of fields
     * @return array : array('found' => true|false, 'errors' => "Error message", 'pk' => "primary key")
     */
    public function importLine($line)
    {
        $plugin = $this->getPlugin();
        $classname = $this['import_type'];
        if (!$classname) {
            return array();
        }
        $data = $this->getMappedData($line);
        $pk = $this->getPrimaryKey($data);
        //Last chance to quit:
        $error = $this->checkLine($line, $data, $pk);

        $output = array();

        $object = new $classname($pk);
        if (!$object->isNew()) {
            $output['found'] = true;
            $output['pk'] = $pk;
            foreach ((array) $this['tabledata']['ignoreonupdate'] as $fieldname) {
                unset($data[$fieldname]);
            }
        } else {
            $output['found'] = false;
        }
        foreach ($data as $fieldname => $value) {
            if (($value !== false) && in_array($fieldname, $this->getTargetFields())) {
                $object[$fieldname] = $value;
                if ($classname === "User" && $fieldname === "password") {
                    $object[$fieldname] = UserManagement::getPwdHasher()->HashPassword($value);
                }
            }
        }
        if (method_exists($object, "getFullName")) {
            $error['name'] = $output['name'] = $object->getFullName();
        } elseif ($object->isField("name")) {
            $error['name'] = $output['name'] = $object['name'];
        } elseif ($object->isField("title")) {
            $error['name'] = $output['name'] = $object['title'];
        }

        if ($error && $error['errors']) {
            //exit here to have the name of the object in the log
            return $error;
        }

        if ($plugin) {
            $plugin->beforeUpdate($object, $line, $data);
        }

        $object->store();

        $output['pk'] = (array) $object->getId();

        //Dynamic special fields:
        switch ($classname) {
            case "Course":
                //fleximport_dozenten
                foreach ($data['fleximport_dozenten'] as $dozent_id) {
                    $seminar = new Seminar($object->getId());
                    $seminar->addMember($dozent_id, 'dozent');
                }

                //fleximport_related_institutes
                if (!$data['fleximport_related_institutes']) {
                    $data['fleximport_related_institutes'] = array($object['institut_id']);
                } else if(!in_array($object['institut_id'], $data['fleximport_related_institutes'])) {
                    $data['fleximport_related_institutes'][] = $object['institut_id'];
                }
                foreach ($data['fleximport_related_institutes'] as $institut_id) {
                    $insert = DBManager::get()->prepare("
                        INSERT IGNORE INTO seminar_inst
                        SET seminar_id = :seminar_id,
                            institut_id = :institut_id
                    ");
                    $insert->execute(array(
                        'seminar_id' => $object->getId(),
                        'institut_id' => $institut_id
                    ));
                }
                if ($this['tabledata']['simplematching']["fleximport_course_userdomains"]['column'] || in_array("fleximport_course_userdomains", $this->fieldsToBeDynamicallyMapped())) {
                    $statement = DBManager::get()->prepare("
                        SELECT userdomain_id
                        FROM seminar_userdomains
                        WHERE seminar_id = ?
                    ");
                    $statement->execute(array($object->getId()));
                    $olddomains = $statement->fetchAll(PDO::FETCH_COLUMN, 0);
                    foreach (array_diff($data['fleximport_user_inst'], $olddomains) as $to_add) {
                        $domain = new UserDomain($to_add);
                        $domain->addSeminar($object->getId());
                    }
                    foreach (array_diff($olddomains, $data['fleximport_user_inst']) as $to_remove) {
                        $domain = new UserDomain($to_remove);
                        $domain->removeSeminar($object->getId());
                    }
                }
                break;
            case "User":
                if ($this['tabledata']['simplematching']["fleximport_user_inst"]['column'] || in_array("fleximport_user_inst", $this->fieldsToBeDynamicallyMapped())) {
                    if ($object['perms'] !== "root") {
                        foreach ($data['fleximport_user_inst'] as $institut_id) {
                            $member = new InstituteMember(array($object->getId(), $institut_id));
                            $member['inst_perms'] = $object['perms'];
                            $member->store();
                        }
                    }
                }
                if ($this['tabledata']['simplematching']["fleximport_userdomains"]['column'] || in_array("fleximport_userdomains", $this->fieldsToBeDynamicallyMapped())) {
                    $olddomains = UserDomain::getUserDomainsForUser($object->getId());
                    foreach ($olddomains as $olddomain) {
                        if (!in_array($olddomain->getID(), (array) $data['fleximport_userdomains'])) {
                            $olddomain->removeUser($object->getId());
                        }
                    }
                    foreach ($data['fleximport_userdomains'] as $userdomain) {
                        $domain = new UserDomain($userdomain);
                        $domain->addUser($object->getId());
                    }
                    AutoInsert::instance()->saveUser($object->getId());

                    foreach ($data['fleximport_userdomains'] as $domain_id) {
                        if (!in_array($domain_id, $olddomains)) {
                            $welcome = FleximportConfig::get("USERDOMAIN_WELCOME_" . $domain_id);
                            if ($welcome) {
                                foreach ($object->toArray() as $field => $value) {
                                    $welcome = str_replace("{{" . $field . "}}", $value, $welcome);
                                }
                                foreach ($line as $field => $value) {
                                    $welcome = str_replace("{{" . $field . "}}", $value, $welcome);
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
                        }
                    }

                }
                if ($this['tabledata']['simplematching']["fleximport_expiration_date"]['column'] || in_array("fleximport_expiration_date", $this->fieldsToBeDynamicallyMapped())) {
                    if ($data['fleximport_expiration_date']) {
                        UserConfig::get($object->getId())->store("EXPIRATION_DATE", $data['fleximport_expiration_date']);
                    } else {
                        UserConfig::get($object->getId())->delete("EXPIRATION_DATE");
                    }
                }
                if (($output['found'] === false) && ($data['fleximport_welcome_message'] !== "none")) {
                    $user_language = getUserLanguagePath($object->getId());
                    setTempLanguage(false, $user_language);
                    if ($data['fleximport_welcome_message'] && FleximportConfig::get($data['fleximport_welcome_message'])) {
                        $message = FleximportConfig::get($data['fleximport_welcome_message']);
                        foreach ($data as $field => $value) {
                            $message = str_replace("{{".$field."}}", $value, $message);
                        }
                        foreach ($line as $field => $value) {
                            if (!in_array($field, $data)) {
                                $message = str_replace("{{".$field."}}", $value, $message);
                            }
                        }
                        if (strpos($message, "\n") === false) {
                            $subject = dgettext($user_language, "Anmeldung Stud.IP-System");
                        } else {
                            $subject = strstr($message, "\n", true);
                            $message = substr($message, strpos($message, "\n") + 1);
                        }
                    } else {
                        $Zeit=date("H:i:s, d.m.Y",time());
                        $this->user_data = array(
                            'auth_user_md5.username' => $object['username'],
                            'auth_user_md5.perms' => $object['perms'],
                            'auth_user_md5.Vorname' => $object['vorname'],
                            'auth_user_md5.Nachname' => $object['nachname'],
                            'auth_user_md5.Email' => $object['email']
                        );
                        $password = $data['password']; //this is the not hashed password in cleartext
                        include("locale/$user_language/LC_MAILS/create_mail.inc.php");
                        $message = $mailbody;
                    }
                    if ($message) {
                        $mail = new StudipMail();
                        $mail->addRecipient($object['email'], $object->getFullName());
                        $mail->setSubject($subject);
                        $mail->setBodyText($message);
                        $mail->setBodyHtml(formatReady($message));
                        if (Config::get()->MAILQUEUE_ENABLE) {
                            MailQueueEntry::add($mail);
                        } else {
                            $mail->send();
                        }
                    }
                    restoreLanguage();
                }
                break;
        }

        //Datafields:
        $datafields = array();
        switch ($classname) {
            case "Course":
                $datafields = Datafield::findBySQL("object_type = 'sem'");
                break;
            case "User":
                $datafields = Datafield::findBySQL("object_type = 'user'");
                break;
            case "CourseMember":
                $datafields = Datafield::findBySQL("object_type = 'usersemdata'");
                break;
        }
        foreach ($datafields as $datafield) {
            $fieldname = $datafield['name'];

            if (isset($data[$fieldname])) {
                $entry = new DatafieldEntryModel(array(
                    $datafield->getId(),
                    $object->getId(),
                    ""
                ));
                $entry['content'] = $data[$fieldname];
                $entry->store();
            }
        }

        if ($classname === "Course") {
            if ($this['tabledata']['simplematching']["fleximport_studyarea"]['column']
                    || in_array("fleximport_studyarea", $this->fieldsToBeDynamicallyMapped())) {
                //Studienbereiche:
                $remove = DBManager::get()->prepare("
                    DELETE FROM seminar_sem_tree
                    WHERE seminar_id = :seminar_id
                ");
                $remove->execute(array(
                    'seminar_id' => $object->getId()
                ));

                if ($GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$data['status']]['class']]['bereiche']) {
                    foreach ($data['fleximport_studyarea'] as $sem_tree_id) {
                        $insert = DBManager::get()->prepare("
                            INSERT IGNORE INTO seminar_sem_tree
                            SET sem_tree_id = :sem_tree_id,
                                seminar_id = :seminar_id
                        ");
                        $insert->execute(array(
                            'sem_tree_id' => $sem_tree_id,
                            'seminar_id' => $object->getId()
                        ));
                    }
                }
            }

            if ($this['tabledata']['simplematching']["fleximport_locked"]['column']
                    || in_array("fleximport_locked", $this->fieldsToBeDynamicallyMapped())) {
                //Lock or unlock course
                if ($data['fleximport_locked']) {
                    CourseSet::addCourseToSet(
                        CourseSet::getGlobalLockedAdmissionSetId(),
                        $object->getId()
                    );
                } elseif (in_array($data['fleximport_locked'], array("0", 0)) && ($data['fleximport_locked'] !== "")) {
                    CourseSet::removeCourseFromSet(
                        CourseSet::getGlobalLockedAdmissionSetId(),
                        $object->getId()
                    );
                }
            }

            $folder_exist = DBManager::get()->prepare("
                SELECT 1 FROM folder WHERE range_id = ?
            ");
            $folder_exist->execute(array($object->getId()));
            if (!$folder_exist->fetch()) {
                $insert_folder = DBManager::get()->prepare("
                    INSERT IGNORE INTO folder
                    SET folder_id = MD5(CONCAT(:seminar_id, 'allgemeine_dateien')),
                    range_id = :seminar_id,
                    user_id = :user_id,
                    name = :name,
                    description = :description,
                    mkdate = UNIX_TIMESTAMP(),
                    chdate = UNIX_TIMESTAMP()
                ");
                $insert_folder->execute(array(
                    'seminar_id' => $object->getId(),
                    'user_id' => $GLOBALS['user']->id,
                    'name' => _("Allgemeiner Dateiordner"),
                    'description' => _("Ablage für allgemeine Ordner und Dokumente der Veranstaltung")
                ));
            }
        }

        if ($plugin && !$object->isNew()) {
            $plugin->afterUpdate($object, $line);
        }
        return $output;
    }

    public function getMappedData($line)
    {
        $plugin = $this->getPlugin();
        $fields = $this->getTargetFields();

        //dynamic additional fields:
        switch ($this['import_type']) {
            case "Course":
                foreach (Datafield::findBySQL("object_type = 'sem'") as $datafield) {
                    $fields[] = $datafield['name'];
                }
                $fields[] = "fleximport_dozenten";
                $fields[] = "fleximport_related_institutes";
                $fields[] = "fleximport_studyarea";
                $fields[] = "fleximport_locked";
                $fields[] = "fleximport_course_userdomains";
                break;
            case "User":
                foreach (Datafield::findBySQL("object_type = 'user'") as $datafield) {
                    $fields[] = $datafield['name'];
                }
                $fields[] = "fleximport_username_prefix";
                $fields[] = "fleximport_userdomains";
                $fields[] = "fleximport_user_inst";
                $fields[] = "fleximport_expiration_date";
                $fields[] = "fleximport_welcome_message";
                break;
        }

        $data = array();

        foreach ($fields as $field) {
            $mapping = false; //important: false means no mapping, null means mapping to database null
            if ($plugin && in_array($field, $plugin->fieldsToBeMapped())) {
                $mapping = $plugin->mapField($field, $line);
            }
            if ($mapping !== false) {
                $data[$field] = $mapping;
            } else {
                if ($this['tabledata']['simplematching'][$field]['column']) {
                    if ($this['tabledata']['simplematching'][$field]['column'] === "static value") {
                        //use a static value
                        $data[$field] = $this['tabledata']['simplematching'][$field]['static'];
                    } else {
                        if (strpos($this['tabledata']['simplematching'][$field]['column'], "fleximportconfig_") === 0) {
                            $config = substr($this['tabledata']['simplematching'][$field]['column'], strlen("fleximportconfig_"));
                            $template = FleximportConfig::get($config);
                            foreach ($data as $index => $value) {
                                $template = str_replace("{{".$index."}}", $value, $template);
                            }
                            foreach ($line as $index => $value) {
                                if (!in_array($index, $data)) {
                                    $template = str_replace("{{".$index."}}", $value, $template);
                                }
                            }
                            $data[$field] = $template;
                        } else {
                            //use a matched column
                            $data[$field] = $line[$this['tabledata']['simplematching'][$field]['column']];
                        }
                    }
                } else {
                    //else no mapping, don't even overwrite old value.
                }
            }
        }

        foreach ($fields as $field) {
            //mapper:
            if (strpos($this['tabledata']['simplematching'][$field]['column'], "fleximport_mapper__") === 0) {
                list($prefix, $mapperclass, $format) = explode("__", $this['tabledata']['simplematching'][$field]['column']);
                if (class_exists($mapperclass)) {
                    $mapper = new $mapperclass();
                    if (is_a($mapper, "FleximportMapper")) {
                        $mapfrom = $this['tabledata']['simplematching'][$field]['mapfrom'];
                        $data[$field] = $mapper->map(
                            $format,
                            $data[$mapfrom] ?: $line[$mapfrom]
                        );
                    }
                }
            }
        }

        //special mapping
        if ($this['import_type'] === "Course") {
            //Map seminar_id :
            if (!$data['seminar_id'] && $this['tabledata']['simplematching']["seminar_id"]['column'] === "fleximport_map_from_veranstaltungsnummer_and_semester") {
                $course = Course::findOneBySQL("name = ? AND start_time = ?", array($data['name'], $data['start_time']));
                if ($course) {
                    $data['seminar_id'] = $course->getId();
                }
            }

            //Map dozenten:
            if ($this['tabledata']['simplematching']["fleximport_dozenten"]['column']
                    && !in_array("fleximport_dozenten", $this->fieldsToBeDynamicallyMapped())) {
                $data['fleximport_dozenten'] = (array) preg_split(
                    $this['tabledata']['simplematching']["fleximport_dozenten"]['format'] === "fullname" ? "/\s*,\s*/" : "/\s+/",
                    $data['fleximport_dozenten'],
                    null,
                    PREG_SPLIT_NO_EMPTY
                );

                switch ($this['tabledata']['simplematching']["fleximport_dozenten"]['format']) {
                    case "user_id":
                        $data['fleximport_dozenten'] = array_map(function ($user_id) {
                            $user = User::find($user_id);
                            if ($user) {
                                return $user->getId();
                            } else {
                                return null;
                            }
                        }, $data['fleximport_dozenten']);
                        break;
                    case "username":
                        $data['fleximport_dozenten'] = array_map("get_userid", $data['fleximport_dozenten']);
                        break;
                    case "email":
                        $data['fleximport_dozenten'] = array_map(function ($email) {
                            $user = User::findOneByEmail($email);
                            if ($user) {
                                return $user->getId();
                            } else {
                                return null;
                            }
                        }, $data['fleximport_dozenten']);
                        break;
                    case "fullname":
                        $data['fleximport_dozenten'] = array_map(function ($fullname) {
                            list($vorname, $nachname) = (array) preg_split("/\s+/", $fullname, null, PREG_SPLIT_NO_EMPTY);
                            $user = User::findOneBySQL("Vorname = ? AND Nachname = ? AND perms = 'dozent'", array($vorname, $nachname));
                            if ($user) {
                                return $user->getId();
                            } else {
                                return null;
                            }
                        }, $data['fleximport_dozenten']);
                        break;
                    default:
                        //map by datafield
                        $datafield_id = $this['tabledata']['simplematching']["fleximport_dozenten"]['format'];
                        foreach ($data['fleximport_dozenten'] as $key => $value) {
                            $entry = DatafieldEntryModel::findOneBySQL("datafield_id = ? AND content = ?", array($datafield_id, $value));
                            if ($entry) {
                                $data['fleximport_dozenten'][$key] = $entry['range_id'];
                            } else {
                                unset($data['fleximport_dozenten'][$key]);
                            }
                        }
                        break;
                }
            }

            //Map sem_type:
            if ($this['tabledata']['simplematching']["status"]['column']
                    && $this['tabledata']['simplematching']["status"]['format']) {
                if ($this['tabledata']['simplematching']["status"]['format'] === "name") {
                    $sem_type_id = null;
                    foreach ($GLOBALS['SEM_TYPE'] as $id => $sem_type) {
                        if ($sem_type['name'] === $data['status']) {
                            $sem_type_id = $id;
                        }
                    }
                    $data['status'] = $sem_type_id;
                }
            }

            //Map Studienbereiche
            if ($this['tabledata']['simplematching']["fleximport_studyarea"]['column'] && !in_array("fleximport_studyarea", $this->fieldsToBeDynamicallyMapped())) {
                if ($this['tabledata']['simplematching']["fleximport_studyarea"]['column'] === "static value") {
                    $data['fleximport_studyarea'] = (array) explode(";", $this['tabledata']['simplematching']["fleximport_studyarea"]['static']);
                } else {
                    $data['fleximport_studyarea'] = (array) explode(";", $data['fleximport_studyarea']);
                    $study_areas = array();
                    foreach ($data['fleximport_studyarea'] as $key => $name) {
                        foreach (StudipStudyArea::findBySQL("name = ?", array($name)) as $study_area) {
                            $study_areas[] = $study_area->getId();
                        }
                    }
                    $data['fleximport_studyarea'] = $study_areas;
                }
            }
            if ($this['tabledata']['simplematching']["fleximport_course_userdomains"]['column'] && !in_array("fleximport_course_userdomains", $this->fieldsToBeDynamicallyMapped())) {
                $data['fleximport_course_userdomains'] = (array) preg_split(
                    "/\s*,\s*/",
                    $data['fleximport_course_userdomains'],
                    null,
                    PREG_SPLIT_NO_EMPTY
                );
                $statement = DBManager::get()->prepare("SELECT userdomain_id FROM userdomains WHERE name IN (:domains) OR userdomain_id IN (:domains)");
                $statement->execute(array('domains' => $data['fleximport_course_userdomains']));
                $data['fleximport_course_userdomains'] = $statement->fetchAll(PDO::FETCH_COLUMN, 0);
            }
        }

        if (($this['import_type'] === "User")) {
            if ($this['tabledata']['simplematching']["username"]['column']) {
                if ($this['tabledata']['simplematching']["username"]['format'] === "email_first_part") {
                    list($data['username']) = explode("@", $data['username']);
                }
                if ($this['tabledata']['simplematching']["fleximport_username_prefix"]['column']) {
                    $data['username'] = $data['fleximport_username_prefix'] . $data['username'];
                }
            }
            if ($this['tabledata']['simplematching']["fleximport_user_inst"]['column']) {
                $data['fleximport_user_inst'] = (array) preg_split(
                    "/\s*,\s*/",
                    $data['fleximport_user_inst'],
                    null,
                    PREG_SPLIT_NO_EMPTY
                );
                $institut_ids = array();
                foreach ($data['fleximport_user_inst'] as $inst_name) {
                    $statement = DBManager::get()->prepare("
                        SELECT Institut_id
                        FROM Institute
                        WHERE Name = ?
                    ");
                    $statement->execute(array($inst_name));
                    $institut_id = $statement->fetch(PDO::FETCH_COLUMN, 0);
                    if ($institut_id) {
                        $institut_ids[] = $institut_id;
                    }
                }
                $data['fleximport_user_inst'] = $institut_ids;
            }
            if ($this['tabledata']['simplematching']["fleximport_userdomains"]['column'] && !in_array("fleximport_userdomains", $this->fieldsToBeDynamicallyMapped())) {
                $data['fleximport_userdomains'] = (array) preg_split(
                    "/\s*,\s*/",
                    $data['fleximport_userdomains'],
                    null,
                    PREG_SPLIT_NO_EMPTY
                );
                $statement = DBManager::get()->prepare("SELECT userdomain_id FROM userdomains WHERE name IN (:domains) OR userdomain_id IN (:domains)");
                $statement->execute(array('domains' => $data['fleximport_userdomains']));
                $data['fleximport_userdomains'] = $statement->fetchAll(PDO::FETCH_COLUMN, 0);
            }
            if ($this['tabledata']['simplematching']["fleximport_expiration_date"]['column'] && !in_array("fleximport_expiration_date", $this->fieldsToBeDynamicallyMapped())) {
                if (!is_numeric($data['fleximport_expiration_date'])) {
                    $data['fleximport_expiration_date'] = strtotime($data['fleximport_expiration_date']);
                }
            }
        }

        if (($this['import_type'] === "User") && !$data['user_id']) {
            if (!$data['user_id'] && ($data['auth_plugin'] === "standard") && !$data['password']) {
                $usermanager = new UserManagement();
                $data['password'] = $usermanager->generate_password(6);
            }
        }
        return $data;
    }

    /**
     * @param $line : associative array of raw data
     * @param null $data : only needed for performance if you already have the data
     * @param null $pk : only needed for performance if you already have the primary key
     * @return array : associative array like array('found' => true, 'pk' => array(), 'errors' => "bla")
     */
    public function checkLine($line, $data = null, $pk = null)
    {
        $plugin = $this->getPlugin();
        $classname = $this['import_type'];

        $output = array(
            'found' => false,
            'pk' => null,
            'errors' => ""
        );

        if ($classname && $classname !== "fleximport_mysql_command") {
            try {
                if ($data === null) {
                    $data = $this->getMappedData($line);
                }
                if ($pk === null) {
                    $pk = $this->getPrimaryKey($data);
                }
            } catch (Exception $e) {
                return array('errors' => "Tabellenmapping ist vermutlich falsch konfiguriert: ".$e->getMessage()." ".$e->getTraceAsString());
            }
            $object = new $classname($pk);
            if (!$object->isNew()) {
                $output['found'] = true;
                $output['pk'] = $pk;
            }
            $object->setData($data);

            //now do the checking
            $checkerclass = "Fleximport".$classname."Checker";
            $relevantfields = $this->fieldsToBeDynamicallyMapped();
            foreach ((array) $this['tabledata']['simplematching'] as $field => $value) {
                if ($value['column']) {
                    $relevantfields[] = $field;
                }
            }
            if (class_exists($checkerclass)) {
                $checker = new $checkerclass();
                if (is_a($checker, "FleximportChecker")) {
                    $output['errors'] .= $checker->check($data, $object, $relevantfields);
                }
            }
        }

        if ($plugin) {
            $output['errors'] .= $plugin->checkLine($line);
        }

        return $output;
    }

    public function getPrimaryKey($data)
    {
        $key = array();

        foreach ($this->getTargetPK() as $field) {
            if ($data[$field]) {
                $key[] = $data[$field];
            }
        }
        return count($key) === count($this->getTargetPK()) ? $key : null;
    }

    public function getTargetFields()
    {
        $classname = $this['import_type'];
        if ($classname && $classname !== "fleximport_mysql_command") {
            $object = new $classname();
            $this->sorm_metadata = $object->getTableMetadata();
            $fields = $this->sorm_metadata['fields'];
            if ($classname === "User") {
                $userinfometadata = $object->info->getTableMetadata();
                $fields = array_merge($fields, $userinfometadata['fields']);
            }
            return array_keys($fields);
        } else {
            return array();
        }
    }

    public function getTargetPK()
    {
        if (count($this->sorm_metadata) === 0 && $classname && $classname !== "fleximport_mysql_command") {
            $classname = $this['import_type'];
            $object = new $classname();
            $this->sorm_metadata = $object->getTableMetadata();
        }
        return $this->sorm_metadata['pk'];
    }

    public function getPlugin() {
        $pluginname = $pluginname = $this['name'];
        if (!$this->plugin && class_exists($pluginname)) {
            $this->plugin = new $pluginname($this);
        }
        return $this->plugin;
    }

    public function fieldsToBeDynamicallyMapped()
    {
        if ($this->getPlugin()) {
            return $this->getPlugin()->fieldsToBeMapped();
        } else {
            return array();
        }
    }

    public function neededConfigs()
    {
        if ($this->getPlugin()) {
            return $this->getPlugin()->neededConfigs();
        } else {
            return array();
        }
    }

}