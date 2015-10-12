<?php

class FleximportTable extends SimpleORMap {

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
        if ($this['source'] === "csv_upload") {
            $statement = DBManager::get()->prepare("
                    SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = :table
            ");
            $statement->execute(array('table' => $this['name']));
            return (bool) $statement->fetch();
        } elseif ($this['source'] === "database") {
            $this->fetchDataFromDatabase();
            return true;
        } elseif($this['source'] === "csv_weblink") {
            $this->fetchDataFromWeblink();
            return true;
        }
    }

    public function getTableHeader()
    {
        $statement = DBManager::get()->prepare("
            SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = :table_name
        ");
        $statement->execute(array('table_name' => $this['name']));
        return $statement->fetchAll(PDO::FETCH_COLUMN, 0);
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
            $fieldname = $this->reduceDiakritikaFromIso88591($fieldname);
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
                $insert_sql .= "`".addslashes($field)."` = ".$db->quote($value)." ";
            }
            $db->exec($insert_sql);
        }
    }

    private function reduceDiakritikaFromIso88591($text) {
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

    private function CSV2Array($content, $delim = ';', $encl = '"', $optional = 1) {
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

    private function getCSVDataFromFile($file_path, $delim = ';', $encl = '"', $optional = 1) {
        return $this->CSV2Array(file_get_contents($file_path), $delim, $encl, $optional);
    }

    private function getCSVDataFromURL($file_path, $delim = ';', $encl = '"', $optional = 1) {
        return $this->CSV2Array(studip_utf8decode(file_get_contents($file_path)), $delim, $encl, $optional);
    }

    public function drop()
    {
        DBManager::get()->exec("DROP TABLE IF EXISTS `".addslashes($this['name'])."` ");
    }

}