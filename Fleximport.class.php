<?php

require_once __DIR__."/classes/FleximportTable.php";

//require_once dirname(__file__)."/ImportPlugin.class.php";
//require_once dirname(__file__)."/classes/NSI_VeranstaltungTable.class.php";

class Fleximport extends StudIPPlugin {

    static public function CSV2Array($content, $delim = ';', $encl = '"', $optional = 1) {
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

    static public function getCSVDataFromFile($file_path) {
        return self::CSV2Array(file_get_contents($file_path));
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
        $text = str_replace(array('Ç','ç','Ğ','Ñ','İ','ñ','ı','ÿ'), array('C','c','D','N','Y','n','y','y') , $text);
        return $text;
    }



    public function __construct() {
        parent::__construct();
        if ($GLOBALS['perm']->have_perm("root")) {
            $navigation = new AutoNavigation($this->getDisplayName(), PluginEngine::getURL($this, array(), 'import/overview'));
            Navigation::addItem('/start/courseimport', $navigation);
            Navigation::addItem('/courseimport', $navigation);

            $navigation = new AutoNavigation(_("Import"), PluginEngine::getURL($this, array(), 'import/overview'));
            Navigation::addItem('/courseimport/overview', $navigation);
        }
    }






    public function show_action() {
        if (!$GLOBALS['perm']->have_perm("root")) {
            throw new AccessDeniedException(_("Sie sind nicht berechtigt, dieses Plugin zu benutzen. Nur Root darf das."));
        }
        //Tabellen rüber ziehen
        if (!Request::submitted("starten") || $GLOBALS['IS_CLI']) {
            try {
                $this->getMSDB();
                foreach ($this->db_tables as $table => $tablename) {
                    $this->transferMsSQLTable($table);
                }
                $this->cleanUpOldData();
                if ($GLOBALS['IS_CLI']) {
                    echo sprintf(_("Um %s ist der Import der Tabellen erfolgreich abgeschlossen."), date("H:i:s")._(" Uhr am ").date("j.n.Y"))."\n";
                }
            } catch (Exception $e) {
                PageLayout::postMessage(MessageBox::error("Tabellentransfer von SAFO hat nicht funktioniert.", array($e->getMessage())));
            }
        }
        parent::show_action();
    }

    protected function transferMsSQLTable($table_name) {
        $db = $this->getMSDB();
        $statement = $db->prepare("
            SELECT * FROM [".addslashes($table_name)."]
        ");
        $statement->execute();
        $values = $statement->fetchAll(PDO::FETCH_ASSOC);
        $columns = $db->query(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ".$db->quote($table_name)." " .
        "")->fetchAll(PDO::FETCH_COLUMN, 0);
        $output = array();
        $output[] = $columns;
        foreach ($values as $data) {
            $line = array();
            foreach ($columns as $column) {
                $line[] = $data[$column];
            }
            $output[] = $line;
        }

        $this->createTable($table_name, $output);
    }

    protected function getMSDB() {
        if (!$this->safo) {
            if (!$GLOBALS['NSI_IMPORT_USE_TUNNEL']) {
                //Produktivversion
                $this->safo = new PDO(
                    "dblib:host=10.40.10.242:1433;dbname=prod",
                    "studip",
                    "s1T2u3D4i5P$"
                );
            } else {
                //Nutzer den VPN-Tunnel, der auf localhost 1434 zeigt
                $this->safo = new PDO(
                    "dblib:host=localhost:1434;dbname=prod",
                    "studip",
                    "s1T2u3D4i5P$"
                );
            }

        }
        return $this->safo;
    }

    protected function cleanUpOldData() {
        $currentsemester = Semester::findCurrent();
        $semester = array_values(Semester::getAll());
        $names = array_map(function ($sem) { return $sem['name']; }, $semester);
        foreach ($semester as $key => $sem) {
            if ($sem->getId() === $currentsemester->getId()) {
                $semester = array_splice($semester, $key - 1);
                break;
            }
        }
        $names = array_map(function ($sem) { return $sem['name']; }, $semester);
        $statement = DBManager::get()->prepare("
            DELETE FROM veranstaltung
            WHERE ausbildungsjahr NOT IN (:names)
        ");
        //$statement->execute(array('names' => $names));
    }

    ////////////////////////////////////////////////////////////////////////////
    //                           map-functions                                //
    ////////////////////////////////////////////////////////////////////////////



    ////////////////////////////////////////////////////////////////////////////
    //                          output-functions                              //
    ////////////////////////////////////////////////////////////////////////////

    protected function getDescription() {
        return $this->getTemplate("head_description.php", null)->render();
    }

    protected function getDisplayName() {
        return _("Veranstaltungsimport");
    }

    protected function getSubmitInfo() {
        $db = DBManager::get();
        $template = $this->getTemplate("submit_info.php", null);
        $semester = $db->query("SELECT * FROM semester_data ORDER BY ende DESC")->fetchAll(PDO::FETCH_ASSOC);
        $template->set_attribute('semester', $semester);
        return $template;
    }
    
}
