<?php

class FleximportTable extends SimpleORMap {

    protected $sorm_metadata = array();
    protected $plugin = null;
    protected $already_fetched = false;

    static public function findAll()
    {
        return self::findBySQL("1=1 ORDER BY position ASC, name ASC");
    }

    static protected function configure($config = array())
    {
        $config['db_table'] = 'fleximport_tables';
        parent::configure($config);
    }

    function __construct($id = null)
    {
        $this->registerCallback('before_store', 'cbSerializeData');
        $this->registerCallback('after_store after_initialize', 'cbUnserializeData');
        parent::__construct($id);
    }

    function cbSerializeData()
    {
        $this->content['tabledata'] = json_encode(studip_utf8encode($this->content['tabledata']));
        $this->content_db['tabledata'] = json_encode(studip_utf8encode($this->content_db['tabledata']));
        return true;
    }

    function cbUnserializeData()
    {
        $this->content['tabledata'] = (array) studip_utf8decode(json_decode($this->content['tabledata'], true));
        $this->content_db['tabledata'] = (array) studip_utf8decode(json_decode($this->content_db['tabledata'], true));
        return true;
    }

    public function isInDatabase()
    {
        $this->fetchData();

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
                }
            } else {
                $this->getPlugin()->fetchData();
            }
        } catch (Exception $e) {
            PageLayout::postMessage(MessageBox::error(sprintf(_("Konnte Tabelle '%s' nicht mit Daten bef�llen."), $this['name'])));
        }
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
        $output[] = $columns;
        foreach ($values as $data) {
            $line = array();
            foreach ($columns as $column) {
                $line[] = $data[$column];
            }
            $output[] = $line;
        }

        $this->createTable($output);
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
            $fieldname = strtolower($this->reduceDiakritikaFromIso88591($fieldname));
            $create_sql .= ", ";
            $create_sql .= "`".addslashes($fieldname)."` TEXT NOT NULL";
        }
        $create_sql .= ", PRIMARY KEY (`IMPORT_TABLE_PRIMARY_KEY`) ";
        $create_sql .= ") ENGINE=MyISAM";
        $db->exec($create_sql);

        foreach ($entries as $line) {
            $insert_sql = "INSERT INTO `".addslashes($this['name'])."` SET ";
            foreach ($headers as $key => $field) {
                $key < 1 || $insert_sql .= ", ";
                $value = trim($line[$key]);
                $field = strtolower($this->reduceDiakritikaFromIso88591($field));
                $insert_sql .= "`".addslashes($field)."` = ".$db->quote($value)." ";
            }
            $db->exec($insert_sql);
        }
    }

    public function reduceDiakritikaFromIso88591($text) {
        $text = str_replace(array("�","�","�","�","�","�","�"), array('ae','Ae','oe','Oe','ue','Ue','ss'), $text);
        $text = str_replace(array('�','�','�','�','�','�'), 'A' , $text);
        $text = str_replace(array('�','�','�','�','�','�'), 'a' , $text);
        $text = str_replace(array('�','�','�','�'), 'E' , $text);
        $text = str_replace(array('�','�','�','�'), 'e' , $text);
        $text = str_replace(array('�','�','�','�'), 'I' , $text);
        $text = str_replace(array('�','�','�','�'), 'i' , $text);
        $text = str_replace(array('�','�','�','�','�'), 'O' , $text);
        $text = str_replace(array('�','�','�','�','�'), 'o' , $text);
        $text = str_replace(array('�','�','�'), 'U' , $text);
        $text = str_replace(array('�','�','�'), 'u' , $text);
        $text = str_replace(array('�','�','�','�','�','�','�','�'), array('C','c','D','N','Y','n','y','y') , $text);
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
        return $this->CSV2Array(file_get_contents($file_path), $delim, $encl, $optional);
    }

    public function getCSVDataFromURL($file_path, $delim = ';', $encl = '"', $optional = 1) {
        return $this->CSV2Array(studip_utf8decode(file_get_contents($file_path)), $delim, $encl, $optional);
    }

    public function drop()
    {
        DBManager::get()->exec("DROP TABLE IF EXISTS `".addslashes($this['name'])."` ");
    }



    public function doImport()
    {
        if (!$this['import_type']) {
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
                $protocol[] = $output['errors'];
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
            echo sprintf(_("%s von %s Datens�tzen der Tabelle %s erfolgreich importiert."), $count_successful, $count, $this['name'])." \n";
        }
        if ($this['synchronization']) {
            $import_type = $this['import_type'];
            $items = FleximportMappedItem::findBySQL(
                "import_type = :import_type AND item_id NOT IN (:ids)",
                array(
                    'import_type' => $this['import_type'],
                    'ids' => $item_ids ?: ""
                )
            );

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
                $mapped = FleximportMappedItem::findbyItemId($item_id, $this['import_type']) ?: new FleximportMappedItem();
                $mapped['import_type'] = $this['import_type'];
                $mapped['item_id'] = $item_id;
                $mapped['chdate'] = time();
                $mapped->store();
            }
        }
        return $protocol;
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
        //Last chance to quit:
        $error = $this->checkLine($line);
        if ($error && $error['errors']) {
            return $error;
        }

        $data = $this->getMappedData($line);
        $pk = $this->getPrimaryKey($data);
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
            }
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
                    'description' => _("Ablage f�r allgemeine Ordner und Dokumente der Veranstaltung")
                ));
            }
        }

        if ($plugin && !$object->isNew()) {
            $plugin->afterUpdate($object, $line);
        }
        return $output;
    }

    public function checkLine($line)
    {
        $plugin = $this->getPlugin();
        $classname = $this['import_type'];

        $output = array(
            'found' => false,
            'pk' => null,
            'errors' => ""
        );

        if ($classname) {
            try {
                $data = $this->getMappedData($line);
                $pk = $this->getPrimaryKey($data);
            } catch (Exception $e) {
                return array('errors' => "Tabellenmapping ist vermutlich falsch konfiguriert: ".$e->getMessage()." ".$e->getTraceAsString());
            }
            $object = new $classname($pk);
            if (!$object->isNew()) {
                $output['found'] = true;
                $output['pk'] = $pk;
            }
            $object->setData($data);

            switch ($classname) {
                case "Course":
                    if (!$data['fleximport_dozenten'] || !count($data['fleximport_dozenten'])) {
                        $output['errors'] .= "Dozent kann nicht gemapped werden. ";
                    } else {
                        $exist = false;
                        foreach ((array)$data['fleximport_dozenten'] as $dozent_id) {
                            if (User::find($dozent_id)) {
                                $exist = true;
                                break;
                            }
                        }
                        if (!$exist) {
                            $output['errors'] .= "Angegebene Dozenten sind nicht im System vorhanden. ";
                        }
                    }

                    if (!$data['institut_id'] || !Institute::find($data['institut_id'])) {
                        $output['errors'] .= "Keine g�ltige Heimateinrichtung. ";
                    }

                    if (!Semester::findByTimestamp($data['start_time'])) {
                        $output['errors'] .= "Semester wurde nicht gefunden. ";
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
                                $output['errors'] .= "Keine (korrekten) Studienbereiche definiert. ";
                            }
                        }
                    } else {
                        $output['errors'] .= "Kein Veranstaltungstyp definiert. ";
                    }

                    break;
                case "User":
                    if (!$data['username']) {
                        $output['errors'] .= "Kein Nutzername. ";
                    } else {
                        $validator = new email_validation_class;
                        if (!$validator->ValidateUsername($data['username'])) {
                            $output['errors'] .= "Nutzername syntaktisch falsch. ";
                        }
                    }
                    if (!$data['email']) {
                        $output['errors'] .= "Keine Email. ";
                    } else {
                        $validator = new email_validation_class;
                        if (!$validator->ValidateEmailAddress($data['email'])) {
                            $output['errors'] .= "Email syntaktisch falsch. ";
                        }
                    }
                    if (!$data['perms'] || !in_array($data['perms'], array("user", "autor", "tutor", "dozent", "admin", "root"))) {
                        $output['errors'] .= "Keine korrekten Perms gesetzt. ";
                    }
                    if (!$data['vorname'] && !$data['nachname']) {
                        $output['errors'] .= "Kein Name gesetzt. ";
                    }
                    break;
            }
        }

        if ($plugin) {
            $output['errors'] .= $plugin->checkLine($line);
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
                    $fields[] = strtolower($datafield['name']);
                }
                $fields[] = "fleximport_dozenten";
                $fields[] = "fleximport_related_institutes";
                $fields[] = "fleximport_studyarea";
                $fields[] = "fleximport_locked";
                break;
            case "User":
                foreach (Datafield::findBySQL("object_type = 'user'") as $datafield) {
                    $fields[] = $datafield['name'];
                }
                $fields[] = "fleximport_userdomains";
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
                        //use a matched column
                        $data[$field] = $line[$this['tabledata']['simplematching'][$field]['column']];
                    }
                } else {
                    //just use a field with the same name if there is one
                    if (isset($line[$field])) {
                        $data[$field] = $line[$field];
                    }
                    //else no mapping, don't even overwrite old value.
                }
            }
        }


        //special mapping
        if (($this['import_type'] === "Course") && !$data['seminar_id']) {
            //Map start_time
            if ($this['tabledata']['simplematching']["start_time"]['column'] === "fleximport_current_semester") {
                $semester = Semester::findCurrent();
                if ($semester) {
                    $data['start_time'] = $semester->beginn;
                }
            } elseif ($this['tabledata']['simplematching']["start_time"]['column'] === "fleximport_next_semester") {
                $semester = Semester::findNext();
                if ($semester) {
                    $data['start_time'] = $semester->beginn;
                }
            } elseif ($this['tabledata']['simplematching']["start_time"]['format']) {
                if ($this['tabledata']['simplematching']["start_time"]['format'] === "name") {
                    $semester = Semester::findOneBySQL("name = ?", array($data['start_time']));
                    if ($semester) {
                        $data['start_time'] = $semester->beginn;
                    } else {
                        $data['start_time'] = null;
                    }
                } //else $data['start_time'] is already a unix-timestamp
            }

            //Map seminar_id :
            if ($this['tabledata']['simplematching']["seminar_id"]['column'] === "fleximport_map_from_veranstaltungsnummer") {
                $course = Course::findOneByVeranstaltungsnummer(
                    $data['veranstaltungsnummer'] ?: $line['veranstaltungsnummer']
                );
                if ($course) {
                    $data['seminar_id'] = $course->getId();
                }
            }
            if ($this['tabledata']['simplematching']["seminar_id"]['column'] === "fleximport_map_from_name") {
                $course = Course::findOneBySQL("name = ? ", array($data['name']));
                if ($course) {
                    $data['seminar_id'] = $course->getId();
                }
            }
            if ($this['tabledata']['simplematching']["seminar_id"]['column'] === "fleximport_map_from_veranstaltungsnummer_and_semester") {
                $course = Course::findOneBySQL("name = ? AND start_time = ?", array($data['name'], $data['start_time']));
                if ($course) {
                    $data['seminar_id'] = $course->getId();
                }
            }

            //Map dozenten:
            if ($this['tabledata']['simplematching']["fleximport_dozenten"]['column'] && !in_array("fleximport_dozenten", $this->fieldsToBeDynamicallyMapped())) {
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

            //Map Institute:
            if ($this['tabledata']['simplematching']["institut_id"]['format']) {
                if ($this['tabledata']['simplematching']["institut_id"]['format'] === "name") {
                    $institut = Institute::findOneBySQL("Name = ?", array($data['institut_id']));
                    if ($institut) {
                        $data['institut_id'] = $institut->getId();
                    } else {
                        $data['institut_id'] = null;
                    }
                } else {
                    $entry = DatafieldEntryModel::findOneBySQL("datafield_id = ? AND content = ?", array(
                        $this['tabledata']['simplematching']["institut_id"]['format'],
                        $data['institut_id']
                    ));
                    if ($entry) {
                        $data['institut_id'] = $entry['range_id'];
                    } else {
                        $data['institut_id'] = null;
                    }
                }
            }

            //Map sem_type:
            if ($this['tabledata']['simplematching']["status"]['format']) {
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
        }

        if (($this['import_type'] === "User") && !$data['user_id']) {
            //Map user_id :
            if ($this['tabledata']['simplematching']["user_id"]['column'] === "fleximport_map_from_username") {
                $user = User::findOneByUsername(
                    $data['username'] ?: $line['username']
                );
                if ($user) {
                    $data['user_id'] = $user->getId();
                }
            }
            if ($this['tabledata']['simplematching']["user_id"]['column'] === "fleximport_map_from_email") {
                $user = User::findOneByEmail(
                    $data['email'] ?: $line['email']
                );
                if ($user) {
                    $data['user_id'] = $user->getId();
                }
            }
            if (strpos($this['tabledata']['simplematching']["user_id"]['column'], "fleximport_map_from_datafield_") === 0) {
                $datafield_id = substr($this['tabledata']['simplematching']["user_id"]['column'], strlen("fleximport_map_from_datafield_"));
                $datafield = new DataField($datafield_id);
                $user = User::findByDatafield(
                    $datafield_id,
                    $data[$datafield['name']] ?: $line[$datafield['name']]
                );
                if ($user[0]) {
                    $user = $user[0];
                    $data['user_id'] = $user->getId();
                }
            }
        }
        return $data;
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
        if ($classname) {
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
        if (count($this->sorm_metadata) === 0) {
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