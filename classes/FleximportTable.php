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
        $config['belongs_to']['process'] = array(
            'class_name' => 'FleximportProcess',
            'foreign_key' => 'process_id'
        );
        $config['registered_callbacks']['before_store'][]     = 'cbSerializeData';
        $config['registered_callbacks']['after_store'][]      = 'cbUnserializeData';
        $config['registered_callbacks']['after_delete'][]      = 'cbDeleteTable';
        $config['registered_callbacks']['after_initialize'][] = 'cbUnserializeData';
        parent::configure($config);
    }

    function cbDeleteTable()
    {
        DBManager::get()->exec("DROP TABLE IF EXISTS `".$this->getDBName()."`;");
        DBManager::get()->exec("DROP VIEW IF EXISTS `".$this->getDBName()."`;");
    }

    function cbSerializeData()
    {
        $this->content['tabledata'] = json_encode($this->content['tabledata']);
        return true;
    }

    function cbUnserializeData()
    {
        $this->content['tabledata'] = (array) json_decode($this->content['tabledata'], true);
        return true;
    }

    public function isInDatabase()
    {
        $statement = DBManager::get()->prepare("
            SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME = :table
                AND TABLE_SCHEMA = :db_name
        ");
        $statement->execute(array(
            'table' => $this->getDBName(),
            'db_name' => $GLOBALS['DB_STUDIP_DATABASE']
        ));
        $is_in_db = $statement->fetch();
        return (bool) $is_in_db;
    }

    public function getDBName()
    {
        if (!$this->getId()) {
            $this->setId($this->getNewId());
        }
        return "fleximport_table_".$this->getId();
    }

    public function needsFetching()
    {
        return $this->customImportEnabled()
            || !in_array($this['source'], array("csv_upload", "extern"));
    }

    public function fetchData()
    {
        if ($this->already_fetched) {
            return;
        }
        $start = time();
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
                } elseif ($this['source'] === "csv_path") {
                    $this->fetchDataFromPath();
                    return;
                } elseif($this['source'] === "csv_studipfile") {
                    $file = FileRef::find($this['tabledata']['weblink']['file_id']);
                    if ($file && $file->file) {
                        $output = $this->getCSVDataFromFile($file->file->getPath(), ";");
                        $headline = array_shift($output);
                        $this->createTable($headline, $output);
                    } else {
                        PageLayout::postError(
                            _("Angegebene Datei konnte nicht im System gefunden werden.")
                        );
                    }
                    return;
                }
            } else {
                $this->getPlugin()->fetchData();
            }
        } catch (Exception $e) {
            PageLayout::postMessage(
                MessageBox::error(
                    sprintf(_("Konnte Tabelle '%s' nicht mit Daten befüllen."), $this['name']),
                    array($e->getMessage())
                )
            );
        }
        $this['last_fetch_duration'] = time() - $start;
        $this->store();
    }

    /**
     * This is a hook to do something after all tables of all processes have been imported.
     * We often use that to delete useless data to save time.
     */
    public function afterDataFetching()
    {
        $plugin = $this->getPlugin();
        if ($plugin) {
            $plugin->afterDataFetching();
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
            'table_name' => $this->getDBName(),
            'db_name' => $GLOBALS['DB_STUDIP_DATABASE']
        ));
        return $statement->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function fetchCount()
    {
        try {
            $statement = DBManager::get()->prepare("
                SELECT COUNT(*) FROM `" . addslashes($this->getDBName()) . "`
            ");
            $statement->execute();
            $count = $statement->fetch(PDO::FETCH_COLUMN);
            return $count;
        } catch(Exception $e) {
            PageLayout::postMessage(MessageBox::error(sprintf(_("Kann %s '%s' nicht abrufen in Datenbank."), $this['source'] !== "sqlview" ? "Tabelle": "View", $this['name'])));
            return 0;
        }
    }

    /**
     * Returns all lins of the database.
     * @param bool $as_statement : if true this function returns an array, if false a PDO-statement
     * @return array|PDOStatement
     */
    public function getLines($as_statement = false)
    {
        $statement = DBManager::get()->prepare("
            SELECT * FROM `".addslashes($this->getDBName())."`
        ");
        $statement->execute();
        if ($as_statement) {
            return $statement;
        } else {
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function fetchDataFromDatabase()
    {
        switch ($this['tabledata']['server']['type']) {
            case "mssql":
                $extern_db = new PDO(
                    "dblib:version=7.4;charset=UTF-8;host=".$this['tabledata']['server']['adress'].":".$this['tabledata']['server']['port'].";dbname=".$this['tabledata']['server']['dbname']."",
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

    public function fetchDataFromPath()
    {
        $output = $this->getCSVDataFromFile($this['tabledata']['weblink']['path'], ";");
        $headline = array_shift($output);
        $this->createTable($headline, $output);
    }

    public function createTable($headers, $entries = array())
    {
        if (!$headers) {
            return;
        }
        foreach ($headers as $header) {
            if (mb_strpos($header, ';') !== false) {
                PageLayout::postError(sprintf(_("Der Spaltenname '%s' ist nicht erlaubt. Er darf aus technischen Gründen kein Semikolon beinhalten."), $header));
                return false;
            }
        }
        $db = DBManager::get();
        $this->drop();
        $create_sql = "CREATE TABLE `".addslashes($this->getDBName())."` (";
        $create_sql .= "`IMPORT_TABLE_PRIMARY_KEY` BIGINT NOT NULL AUTO_INCREMENT ";
        $headers = array_map(function ($h) { return strtolower(self::reduceDiakritikaFromIso88591($h)); }, $headers);
        foreach ($headers as $key => $fieldname) {
            if ($fieldname) {
                $create_sql .= ", ";
                $create_sql .= "`" . addslashes($fieldname) . "` TEXT NULL";
            }
        }
        $create_sql .= ", PRIMARY KEY (`IMPORT_TABLE_PRIMARY_KEY`) ";

        foreach ((array) $this['tabledata']['add_index'] as $index_column) {
            if (in_array($index_column, $headers)) {
                $create_sql .= ", KEY `" . $index_column . "` (`" . $index_column . "`(64)) ";
            }
        }
        $create_sql .= ") ";
        $db->exec($create_sql);

        $this->addEntries($headers, $entries);
        return true;
    }

    public function addEntries($headers, $entries = array())
    {
        if (!$headers) {
            return;
        }
        $db = DBManager::get();
        foreach ($entries as $line) {
            $insertable = false;
            foreach ($headers as $key => $fieldname) {
                if (trim($line[$key])) {
                    $insertable = true;
                    break;
                }
            }
            if ($insertable) {
                $insert_sql = "INSERT INTO `" . addslashes($this->getDBName()) . "` SET ";
                foreach ($headers as $key => $field) {
                    if ($field) {
                        $key < 1 || $insert_sql .= ", ";
                        if (is_object($line[$key])) {
                            $value = json_encode((array) $line[$key]);
                        } else {
                            $value = trim($line[$key]);
                        }
                        $field = strtolower(self::reduceDiakritikaFromIso88591($field));
                        $insert_sql .= "`" . addslashes($field) . "` = " . $db->quote($value) . " ";
                    }
                }
                $db->exec($insert_sql);
            }
        }
    }

    static public function reduceDiakritikaFromIso88591($text)
    {
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

    public function CSV2Array($content, $delim = ';', $encl = '"', $optional = 1)
    {
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

    public function getCSVDataFromFile($file_path, $delim = ';', $encl = '"', $optional = 1)
    {
        if (!file_exists($file_path)) {
            PageLayout::postError(_("Datei ist nicht vorhanden."));
            return array();
        }
        if (!is_readable($file_path)) {
            PageLayout::postError(_("Kann Daten nicht lesen."));
            return array();
        }
        $contents = file_get_contents($file_path);
        $bom = pack('H*','EFBBBF');
        $contents = preg_replace("/^$bom/", '', $contents);
        if ($this['tabledata']['source_encoding'] !== "utf8") {
            $contents = mb_convert_encoding($contents, "UTF-8", "Windows-1252");
        }
        return $this->CSV2Array($contents, $delim, $encl, $optional);
    }

    public function getCSVDataFromURL($file_path, $delim = ';', $encl = '"', $optional = 1)
    {
        $contents = file_get_contents($file_path);
        $bom = pack('H*','EFBBBF');
        $contents = preg_replace("/^$bom/", '', $contents);
        if ($this['tabledata']['source_encoding'] !== "utf8") {
            $contents = mb_convert_encoding($contents, "UTF-8", "Windows-1252");
        }
        return $this->CSV2Array($contents, $delim, $encl, $optional);
    }

    public function drop()
    {
        DBManager::get()->exec("DROP TABLE IF EXISTS `".addslashes($this->getDBName())."` ");
        DBManager::get()->exec("DROP VIEW IF EXISTS `".addslashes($this->getDBName())."` ");
    }



    public function doImport()
    {
        $start = time();
        if (!$this['import_type']) {
            return array();
        }
        if ($this['import_type'] === "fleximport_mysql_command") {
            $command = $this['tabledata']['fleximport_mysql_command'];
            $command = preg_split("/\s;\s/", $command, -1, PREG_SPLIT_NO_EMPTY);
            if ($command) {
                foreach ($command as $cmd) {
                    $statement = DBManager::get()->prepare($cmd);
                    $statement->execute();
                }
            }
            return array();
        }
        $statement = DBManager::get()->prepare("
            SELECT * FROM `".addslashes($this->getDBName())."`
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

                    if ($import_type === "User" && class_exists("UserManagement") && User::find($object->getId())) {
                        //for users we use the UserManagement class:
                        try {
                            $usermanager = new UserManagement($object->getId());
                            $success = $usermanager->deleteUser();
                        } catch (Exception $e) {
                            PageLayout::postError($e->getMessage(), [$e->getTraceAsString()]);
                            $success = false;
                        }
                    }
                    if ($object) {
                        $object->delete();
                    }

                }
                if ($item) {
                    $item->delete();
                }
            }
            foreach ($item_ids as $item_id) {
                $mapped = FleximportMappedItem::findbyItemId($item_id, $this->getId()) ?: new FleximportMappedItem();
                $mapped['table_id'] = $this->getId();
                $mapped['item_id'] = $item_id;
                $mapped['chdate'] = time();
                $mapped->store();
            }
        }
        $this['last_import_duration'] = time() - $start;
        $this->store();
        return $protocol;
    }

    public function findDeletableItems($not = array())
    {
        $class = $this['import_type'];
        if (!$class) {
            return [];
        }
        $obj = new $class();
        $pk = $this->getTargetPK();
        $is_multiple_keyed = count($pk) > 1;
        $meta = $obj->getTableMetadata();
        $table = $meta['table'];
        if ($is_multiple_keyed) {
            return FleximportMappedItem::findBySQL(
                "`fleximport_mapped_items`.`table_id` = :table_id
                    AND `fleximport_mapped_items`.`item_id` NOT IN (:ids) "
                    .($this['sync_constraints'] ? 'AND ('.$this['sync_constraints'].')' : ''),
                array(
                    'table_id' => $this->getId(),
                    'ids' => $not ?: ""
                )
            );
        } else {
            return FleximportMappedItem::findBySQL(
                    "INNER JOIN `".addslashes($table)."` ON (`".addslashes($table)."`.`".addslashes($pk[0])."` = `fleximport_mapped_items`.`item_id`) WHERE ".
                "`fleximport_mapped_items`.`table_id` = :table_id
                    AND `fleximport_mapped_items`.`item_id` NOT IN (:ids) "
                    .($this['sync_constraints'] ? 'AND ('.$this['sync_constraints'].')' : ''),
                array(
                    'table_id' => $this->getId(),
                    'ids' => $not ?: ""
                )
            );
        }
    }

    public function countDeletableItems($not = array())
    {
        $class = $this['import_type'];
        if (!$class) {
            return 0;
        }
        $obj = new $class();
        $pk = $this->getTargetPK();
        $is_multiple_keyed = count($pk) > 1;
        $meta = $obj->getTableMetadata();
        $table = $meta['table'];
        if ($is_multiple_keyed) {
            return FleximportMappedItem::countBySQL(
                "`fleximport_mapped_items`.`table_id` = :table_id
                    AND `fleximport_mapped_items`.`item_id` NOT IN (:ids) "
                    .($this['sync_constraints'] ? 'AND ('.$this['sync_constraints'].')' : ''),
                array(
                    'table_id' => $this->getId(),
                    'ids' => $not ?: ""
                )
            );
        } else {
            return FleximportMappedItem::countBySQL(
                "INNER JOIN `".addslashes($table)."` ON (`".addslashes($table)."`.`".addslashes($pk[0])."` = `fleximport_mapped_items`.`item_id`) WHERE ".
                "`fleximport_mapped_items`.`table_id` = :table_id
                    AND `fleximport_mapped_items`.`item_id` NOT IN (:ids) "
                    .($this['sync_constraints'] ? 'AND ('.$this['sync_constraints'].')' : ''),
                array(
                    'table_id' => $this->getId(),
                    'ids' => $not ?: ""
                )
            );
        }
    }

    public function clearIndicators()
    {
        return FleximportMappedItem::deleteBySQL(
            "table_id = :table_id",
            array(
                'table_id' => $this->getId()
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
            FROM `".addslashes($this->getDBName())."`
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

        //get dynamics special fields:
        $dynamics = array();
        foreach (get_declared_classes() as $class) {
            if (is_subclass_of($class, "FleximportDynamic") && ($class !== "FleximportDynamic")) {
                $dynamics[] = new $class();
            }
        }

        //apply values to dynamic special fields before storing:
        foreach ($dynamics as $dynamic) {
            $for = $dynamic->forClassFields();
            $for = array_merge( (array) $for['*'], (array) $for[$classname]);
            foreach ($for as $fieldname => $placeholder) {
                if (isset($data[$fieldname])) {
                    $dynamic->applyValueBeforeStore($object, $data[$fieldname], $line, (bool) $this['tabledata']['simplematching'][$fieldname]['sync']);
                }
            }
        }

        try {
            $object->store();
        } catch(InvalidValuesException $e) {
            PageLayout::postError($e->getMessage(), [
                json_encode($object->toRawArray())
            ]);
            return;
        }

        $output['pk'] = (array) $object->getId();

        //apply values to dynamic special fields:
        foreach ($dynamics as $dynamic) {
            $for = $dynamic->forClassFields();
            $for = array_merge( (array) $for['*'], (array) $for[$classname]);
            foreach ($for as $fieldname => $placeholder) {
                if (isset($data[$fieldname])) {
                    $dynamic->applyValue(
                        $object,
                        $data[$fieldname],
                        $line,
                        (bool) $this['tabledata']['simplematching'][$fieldname]['sync']
                    );
                }
            }
        }

        switch ($classname) {
            case "Course":
                //Even if no fleximport_studyarea is mapped, we need something in the seminar_inst table:
                $insert = DBManager::get()->prepare("
                    INSERT IGNORE INTO seminar_inst
                    SET seminar_id = :seminar_id,
                        institut_id = :institut_id
                ");
                $insert->execute(array(
                    'seminar_id' => $object->getId(),
                    'institut_id' => $object['institut_id']
                ));
                break;
            case "CourseMember":
                if (($output['found'] === false) && $this['tabledata']['simplematching']['fleximport_welcome_message']['column']) {
                    $user_language = getUserLanguagePath($object['user_id']);
                    $column = $this['tabledata']['simplematching']['fleximport_welcome_message']['column'];
                    setTempLanguage(false, $user_language);
                    if ($column && FleximportConfig::get($column)) {
                        $message = FleximportConfig::get($column);
                        $message = FleximportConfig::template($message, $data, $line);
                    } else {
                        $message = sprintf(_('Sie wurden als TeilnehmerIn in die Veranstaltung **%s** eingetragen.'), $object->course->name);
                    }
                    if ($message) {
                        $messaging = new messaging();
                        $messaging->insert_message(
                            $message,
                            get_username($object['user_id']),
                            '____%system%____',
                            FALSE,
                            FALSE,
                            '1',
                            FALSE,
                            sprintf('%s %s', _('Systemnachricht:'), _('Eintragung in Veranstaltung')),
                            TRUE
                        );
                    }
                    restoreLanguage();
                }
                if ($object->course->parent_course) {
                    $parent = new Seminar($object->course->parent_course);
                    $parent->addMember(
                        $object['user_id'],
                        $object['status']
                    );
                }
                break;
            case "User":
                if (($output['found'] === false) && ($this['tabledata']['simplematching']['fleximport_welcome_message']['column'] !== "none")) {
                    $user_language = getUserLanguagePath($object->getId());
                    setTempLanguage(false, $user_language);
                    if ($this['tabledata']['simplematching']['fleximport_welcome_message']['column'] && FleximportConfig::get($this['tabledata']['simplematching']['fleximport_welcome_message']['column'])) {
                        $message = FleximportConfig::get($this['tabledata']['simplematching']['fleximport_welcome_message']['column']);
                        $message = FleximportConfig::template($message, $data, $line);
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
                        $password = $data['password_plaintext']; //this is the not hashed password in cleartext
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
                if ($output['found'] === false) {
                    StudipLog::log(
                        "USER_CREATE",
                        $object->getId(),
                        null,
                        "Durch Fleximport der Tabelle ".$this['name'].": " . join(';', $object->toArray('username vorname nachname perms email'))
                    );
                }
                AutoInsert::instance()->saveUser($object->getId());
                break;
        }

        //Datafields:
        $datafields = array();
        switch ($classname) {
            case "Course":
                $datafields = DataField::findBySQL("object_type = 'sem'");
                break;
            case "Institute":
                $datafields = DataField::findBySQL("object_type = 'inst'");
                break;
            case "User":
                $datafields = DataField::findBySQL("object_type = 'user'");
                break;
            case "CourseMember":
                $datafields = DataField::findBySQL("object_type = 'usersemdata'");
                break;
        }
        foreach ($datafields as $datafield) {
            $fieldname = $datafield['name'];

            if (isset($data[$fieldname])) {
                $id = array($datafield->getId());
                foreach (array_reverse((array) $object->getId()) as $id_part) {
                    $id[] = $id_part;
                }
                if (count($id) < 3) {
                    $id[] = "";
                }
                if (StudipVersion::newerThan("4.0") && count($id) < 4) {
                    $id[] = "";
                }
                $entry = new DatafieldEntryModel($id);
                $entry['content'] = $data[$fieldname];
                $entry->store();
            }
        }
        if ($classname === "Resource" && StudipVersion::newerThan("4.4.99")) {
            foreach (ResourcePropertyDefinition::findBySQL("1 ORDER BY name") as $property) {
                if (isset($data[$property['name']])) {
                    $id = array($property->getId());
                    foreach (array_reverse((array) $object->getId()) as $id_part) {
                        $id[] = $id_part;
                    }
                    if (count($id) < 3) {
                        $id[] = "";
                    }
                    $entry = new ResourceProperty([
                        $object->getId(),
                        $property->getId()
                    ]);
                    $entry['state'] = $data[$fieldname];
                    $entry->store();
                }
            }
        }

        if ($plugin && !$object->isNew()) {
            $plugin->afterUpdate($object, $line);
        }
        return $output;
    }

    /**
     * Takes the raw data of the dataline and returns the mapped data as an associative array
     * @param array $line
     * @return array mapped data
     */
    public function getMappedData($line)
    {
        $plugin = $this->getPlugin();
        $fields = $this->getTargetFields();

        $dynamics = array();
        foreach (get_declared_classes() as $class) {
            if (is_subclass_of($class, "FleximportDynamic") && ($class !== "FleximportDynamic")) {
                $dynamics[] = new $class();
            }
        }

        //dynamic additional fields:
        switch ($this['import_type']) {
            case "Course":
                foreach (DataField::findBySQL("object_type = 'sem'") as $datafield) {
                    $fields[] = is_string($datafield['name'])
                        ? $datafield['name']
                        : $datafield['name']->original(); //this is an I18NString and we need the original name of the datafield
                }
                break;
            case "Institute":
                foreach (DataField::findBySQL("object_type = 'inst'") as $datafield) {
                    $fields[] = is_string($datafield['name'])
                        ? $datafield['name']
                        : $datafield['name']->original();
                }
                break;
            case "User":
                foreach (DataField::findBySQL("object_type = 'user'") as $datafield) {
                    $fields[] = is_string($datafield['name'])
                        ? $datafield['name']
                        : $datafield['name']->original();
                }
                $fields[] = "fleximport_username_prefix";
                $fields[] = "fleximport_welcome_message";
                break;
            case "CourseMember":
                foreach (DataField::findBySQL("object_type = 'usersemdata'") as $datafield) {
                    $fields[] = is_string($datafield['name'])
                        ? $datafield['name']
                        : $datafield['name']->original();
                }
                $fields[] = "fleximport_welcome_message";
                break;
        }
        if ($this['import_type'] === "Resource" && StudipVersion::newerThan("4.4.99")) {
            foreach (ResourcePropertyDefinition::findBySQL("1 ORDER BY name") as $property) {
                $fields[] = $property['name'];
            }
        }
        foreach ($dynamics as $dynamic) {
            $for = $dynamic->forClassFields();
            $for = array_merge((array) $for['*'], (array) $for[$this['import_type']]);
            foreach ($for as $fieldname => $placeholder) {
                $fields[] = $fieldname;
            }
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
                            //Mapping with templates:
                            $config = substr($this['tabledata']['simplematching'][$field]['column'], strlen("fleximportconfig_"));
                            $template = FleximportConfig::get($config);
                            $data[$field] = FleximportConfig::template($template, $data, $line);
                        } elseif(strpos($this['tabledata']['simplematching'][$field]['column'], "fleximportkeyvalue_") === 0) {
                            $config = substr($this['tabledata']['simplematching'][$field]['column'], strlen("fleximportkeyvalue_"));
                            $map = parse_ini_string(FleximportConfig::get($config));
                            $mapfrom = $this['tabledata']['simplematching'][$field]['mapfrom'] ?: $this['tabledata']['simplematching'][$field]['column'];
                            if (strpos($mapfrom, "fleximportconfig_") === 0) {
                                $config = substr($mapfrom, strlen("fleximportconfig_"));
                                $template = FleximportConfig::get($config);
                                $value = FleximportConfig::template($template, $data, $line);
                            } else {
                                $value = $data[$field] ?: ($data[$mapfrom] ?: $line[$mapfrom]);
                            }
                            if (isset($map[$value])) {
                                $value = FleximportConfig::template($map[$value], $data, $line);
                            } elseif(isset($map["default"])) {
                                $value = FleximportConfig::template($map["default"], $data, $line);
                            } else {
                                $value = "";
                            }
                            $data[$field] = $value;
                        } elseif($field == "fleximport_welcome_message" && FleximportConfig::get($this['tabledata']['simplematching'][$field]['column'])) {
                            $data[$field] = $this['tabledata']['simplematching'][$field]['column'];
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

        //Trennen der Werte der Felder, die multiple Werte enthalten sollen wie fleximport_dozenten
        foreach ($fields as $field) {
            if ($this['tabledata']['simplematching'][$field]['column'] && (!$plugin || !in_array($field, $plugin->fieldsToBeMapped()))) {
                foreach ($dynamics as $dynamic) {
                    $for = $dynamic->forClassFields();
                    if ((isset($for[$this['import_type']][$field]) || isset($for['*'][$field])) && $dynamic->isMultiple()) {
                        if (!$data[$field]) {
                            $mapfrom = $this['tabledata']['simplematching'][$field]['mapfrom'] ?: $this['tabledata']['simplematching'][$field]['column'];
                            if (strpos($mapfrom, "fleximportconfig_") === 0) {
                                $config = substr($mapfrom, strlen("fleximportconfig_"));
                                $template = FleximportConfig::get($config);
                                $value = FleximportConfig::template($template, $data, $line);
                            } else {
                                $value = $data[$mapfrom] ?: $line[$mapfrom];
                            }
                        } else {
                            $value = $data[$field];
                        }
                        $delimiter = $this['tabledata']['simplematching'][$field]['delimiter'] ?: ";";
                        $value = (array) preg_split(
                            "/\s*" . preg_quote($delimiter) . "\s*/",
                            $value,
                            null,
                            PREG_SPLIT_NO_EMPTY
                        );
                        if ($this['tabledata']['simplematching'][$field]['dynamic_template']) {
                            $configname = $this['tabledata']['simplematching'][$field]['dynamic_template'];
                            foreach ($value as $i => $val) {
                                $template = FleximportConfig::get($configname);
                                $value[$i] = FleximportConfig::template($template, $data, $line, $val);
                            }
                        }
                        $data[$field] = $value;
                    }
                }
            }
        }

        foreach (array_reverse($fields) as $field) {
            //Mappen der Werte mit FleximportMappern:
            if (strpos($this['tabledata']['simplematching'][$field]['column'], "fleximport_mapper__") === 0) {
                list($prefix, $mapperclass, $format) = explode("__", $this['tabledata']['simplematching'][$field]['column']);
                if (class_exists($mapperclass)) {
                    $mapper = new $mapperclass();
                    if (is_a($mapper, "FleximportMapper")) {

                        $mapfrom = $this['tabledata']['simplematching'][$field]['mapfrom'] ?: $this['tabledata']['simplematching'][$field]['column'];
                        if (strpos($mapfrom, "fleximportconfig_") === 0) {
                            $config = substr($mapfrom, strlen("fleximportconfig_"));
                            $template = FleximportConfig::get($config);
                            $value = FleximportConfig::template($template, $data, $line);
                        } else {
                            $value = $data[$field] ?: ($line[$mapfrom] ?: $data[$mapfrom]);
                        }
                        //Anwenden der Mapper:
                        $sormclass = $this['tabledata']['simplematching'][$field]['fleximport_foreign_key_sormclass']
                            ?: $this['import_type'];
                        if (is_array($value)) {
                            foreach ($value as $k => $v) {
                                if ($v) {
                                    $value[$k] = $mapper->map(
                                        $format,
                                        $v,
                                        $data,
                                        $sormclass
                                    );
                                }
                            }
                            $data[$field] = $value;
                        } else {
                            $data[$field] = $mapper->map(
                                $format,
                                $value,
                                $data,
                                $sormclass
                            );
                        }
                    }
                }
            }
        }

        //special mapping
        if ($this['import_type'] === "Course") {
            //Map seminar_id :
            if (!$data['seminar_id'] && $this['tabledata']['simplematching']["seminar_id"]['column'] === "fleximport_map_from_veranstaltungsnummer_and_semester") {
                $course = Course::findOneBySQL("VeranstaltungsNummer = ? AND start_time = ?", array($data['veranstaltungsnummer'], $data['start_time']));
                if ($course) {
                    $data['seminar_id'] = $course->getId();
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

            //Map Domains //TODO: change this to a mapper class
            if ($this['tabledata']['simplematching']["fleximport_course_userdomains"]['column'] && !in_array("fleximport_course_userdomains", $this->fieldsToBeDynamicallyMapped())) {
                $statement = DBManager::get()->prepare("SELECT userdomain_id FROM userdomains WHERE name IN (:domains) OR userdomain_id IN (:domains)");
                $statement->execute(array('domains' => $data['fleximport_course_userdomains']));
                $data['fleximport_course_userdomains'] = $statement->fetchAll(PDO::FETCH_COLUMN, 0);
            }
        }

        if ($this['import_type'] === "Institute") {
            if ($data['fakultaets_id'] === "fakultaet") {
                if (!$data['institut_id']) {
                    do {
                        $new_id = md5(uniqid());
                    } while (Institute::find($new_id));
                    $data['institut_id'] = $new_id;
                }
                $data['fakultaets_id'] = $data['institut_id'];
            }
        }

        if (($this['import_type'] === "User")) {
            if ($this['tabledata']['simplematching']["username"]['column']) {
                if ($this['tabledata']['simplematching']["fleximport_username_prefix"]['column']) {
                    $data['username'] = $data['fleximport_username_prefix'] . $data['username'];
                }
            }
            //Passwort hashen und das Klartextpasswort in Variable speichern für die zu versendende Passwortmail:
            if ($this['tabledata']['simplematching']['password']['column']
                && $this['tabledata']['simplematching']['password']['hash']) {
                $data['password_plaintext'] = $data['password'];
                $data['password'] = UserManagement::getPwdHasher()->HashPassword($data['password']);
            }
            if ($this['tabledata']['simplematching']["fleximport_userdomains"]['column'] && !in_array("fleximport_userdomains", $this->fieldsToBeDynamicallyMapped())) {
                $statement = DBManager::get()->prepare("SELECT userdomain_id FROM userdomains WHERE name IN (:domains) OR userdomain_id IN (:domains)");
                $statement->execute(array('domains' => $data['fleximport_userdomains']));
                $data['fleximport_userdomains'] = $statement->fetchAll(PDO::FETCH_COLUMN, 0);
            }

        }

        return $data;
    }

    public function getPrimaryKeyForLine($line, $data = null, $pk = null)
    {
        $classname = $this['import_type'];

        $output = [
            'found' => false,
            'pk' => null,
            'errors' => ""
        ];
        if ($classname && ($classname !== "fleximport_mysql_command") && !class_exists($classname)) {
            $output['errors'] = sprintf(_("Klasse %s existiert nicht."), $classname);
            return $output;
        }

        if ($classname && ($classname !== "fleximport_mysql_command") && class_exists($classname)) {
            try {
                if ($data === null) {
                    $data = $this->getMappedData($line);
                }
                if ($pk === null) {
                    return $this->getPrimaryKey($data);
                }
            } catch (Exception $e) {
                PageLayout::postError("Tabellenmapping ist vermutlich falsch konfiguriert: " . $e->getMessage() . " " . $e->getTraceAsString());
            }
        }
        return null;
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
        if ($classname && ($classname !== "fleximport_mysql_command") && !class_exists($classname)) {
            $output['errors'] = sprintf(_("Klasse %s existiert nicht."), $classname);
            return $output;
        }

        if ($classname && ($classname !== "fleximport_mysql_command") && class_exists($classname)) {
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
            } else {
                $sorm_metadata = $object->getTableMetadata();
                $fields = $sorm_metadata['fields'];
                foreach ($fields as $field) {
                    if (($field['null'] === "NO")
                            && !$field['default']
                            && ($object[$field['name']] === null)
                            && (count($sorm_metadata['pk']) !== 1 || !in_array($field['name'], $sorm_metadata['pk']))
                            && !in_array($field['name'], array("mkdate", "chdate"))) {
                        $output['errors'] .= "Feld ".$field['name']." nicht gesetzt. ";
                    }
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
        $classname = $this['import_type'];
        if (count($this->sorm_metadata) === 0 && $classname && $classname !== "fleximport_mysql_command") {
            $object = new $classname();
            $this->sorm_metadata = $object->getTableMetadata();
        }
        return $this->sorm_metadata['pk'];
    }

    public function getPlugin()
    {
        $pluginname = $this['name'];
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

    public function neededProcessConfigs()
    {
        if ($this->getPlugin()) {
            return $this->getPlugin()->neededProcessConfigs();
        } else {
            return [];
        }
    }

    public function hasChangedHash()
    {
        return true;
    }

    public function updateChangeHash()
    {
        $this['change_hash'] = $this->calculateChangeHash();
    }

    public function calculateChangeHash()
    {
        $statement = DBManager::get()->prepare("CHECKSUM TABLE `".addslashes($this->getDBName())."`");
        $statement->execute();
        $data = $statement->fetch(PDO::FETCH_ASSOC);
        if ($data['Checksum']) {
            return $data['Checksum'];
        } else {
            //wir haben vermutlich einen View und müssen den Hash selbst berechnen

            $statement = DBManager::get()->prepare("SELECT CRC32(SUM(CRC32(CONCAT_WS(`".implode("`,`", $this->getTableHeader())."`)))) AS hash FROM `".addslashes($this->getDBName())."`;");
            $statement->execute();
            $data = $statement->fetch(PDO::FETCH_COLUMN, 0);
            return $data ?: floor(time() / (60 * 15));
        }
    }

}
