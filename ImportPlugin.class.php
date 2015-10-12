<?php
/*
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Rasmus Fuhse <fuhse@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP-Plugins
 * @since       2.1
 */

require_once dirname(__file__)."/classes/ImportDataTable.class.php";

class ImportPlugin extends StudIPPlugin implements SystemPlugin {

    private $template_factory = null;
    
    //Welche Tabellen existieren für den Import: array('tabellenname' => "Schriftlicher Name")
    protected $db_tables = array('import_plugin_data' => "CSV");
    //Callback-Funktionen für Felder in der Form array("tabelle.feldname" => "myclass::map_function")
    protected $map_values = array();
    //Callback-Funktionen für die Ausgabe von eventuell schlecht lesbaren Daten in Feldern
    //in der Form array("tabelle.feldname" => "myclass::map_function")
    protected $map_output = array();
    //Soll ein Upload erlaubt sein (true) oder werden die Tabellen erwartet (false)?
    protected $enable_upload = true;
    //Typ der Tabellen als PHP-Klasse
    protected $table_class = array(
        'import_plugin_data' => "ImportDataTable"
    );
    //columns of the table that don't need to be displayed
    protected $invisible_columns = array('import_plugin_data' => array());
    //the well known variable for messages:
    protected $msg = array();

    public function show_action() {
        if (!$GLOBALS['perm']->have_perm("root")) {
            throw new AccessDeniedException(_("Sie sind nicht berechtigt, dieses Plugin zu benutzen. Nur Root darf das."));
        }
        PageLayout::addScript("jquery.tablesorter.min.js");
        //Upload-Prozedur:
        if ($this->enable_upload) {
            foreach ($this->db_tables as $db_table => $description) {
                if (isset($_FILES[$db_table.'_file'])) {
                    $this->handleUpload($db_table);
                }
            }
        }

        if (Request::get("reset") && in_array(Request::get("table"), array_keys($this->db_tables))) {
            $table_class = Request::get("table");
            $table = new $table_class(Request::get("table"));
            $table->drop();
            $this->msg[] = array("info", _("Tabelle wurde verworfen. Laden Sie sie neu hoch."));
        } elseif ((Request::submitted("starten") && count($_POST)) || $GLOBALS['IS_CLI']) {
            //nicht verwendete Datensätze aus den Tabellen löschen:
            /*$checkboxes = Request::getArray("dataset");
            foreach ($this->db_tables as $db_table => $filename) {
                if ($checkboxes[$db_table]) {
                    $table_class = $this->table_class[$db_table];
                    $table = new $table_class($db_table);
                    $table->stripUnwantedLines(array_keys($checkboxes[$db_table]));
                }
            }*/

            if ($GLOBALS['IS_CLI']) {
                $this->removeErrorLines();
            }
            //Prozessur starten:
            $this->processImport();
        }
        
        if (!$GLOBALS['IS_CLI']) {
            $template = $this->getTemplate("show.php");
        } else {
            $template = $this->getTemplate("show_cli.php", null);
        }
        $template->set_attribute('db_tables', $this->db_tables);
        $template->set_attribute('description', $this->getDescription());
        $template->set_attribute('table_info', $this->getTableInfo());
        $template->set_attribute('map_output', $this->map_output);
        $template->set_attribute('submit_info', $this->getSubmitInfo());
        $template->set_attribute('invisible_tables', $this->invisible_tables);
        $template->set_attribute('plugin', $this);
        $template->set_attribute('msg', $this->msg);
        print $template->render();
    }

    public function upload_enabled() {
        return $this->enable_upload;
    }

    ////////////////////////////////////////////////////////////////////////////
    //                           protected methods                            //
    ////////////////////////////////////////////////////////////////////////////

    /**
     * does the whole import from the given DB-tables
     * should be overwritten by your own plugin
     */
    protected function processImport() {
        foreach ($this->db_tables as $db_table => $filename) {
            if ($GLOBALS['IS_CLI']) {
                echo sprintf(_("Um %s wird angefangen, die Tabelle %s zu prozessieren."), date("H:i:s")._(" Uhr am ").date("j.n.Y"), $db_table)."\n";
            }
            $table_class = $this->table_class[$db_table];
            $table = new $table_class($db_table);
            $parameter = array();
            if (Request::get("semester_id")) {
                $parameter['semester_id'] = Request::option("semester_id");
            }
            $table->process($parameter);
            foreach ($table->getMsg() as $message) {
                //if ($message[0] === "error") {
                    $this->msg[] = $message;
                //}
            }
            if ($GLOBALS['IS_CLI']) {
                echo sprintf(_("Um %s ist die Tabelle %s fertig prozessiert."), date("H:i:s")._(" Uhr am ").date("j.n.Y"), $db_table)."\n";
            }
        }
        foreach ($this->db_tables as $db_table => $filename) {
            $table_class = $this->table_class[$db_table];
            $table = new $table_class($db_table);
            $table->drop();
        }
        //$this->msg = array_merge($this->msg, $table->getMsg());
        //$this->msg[] = array("success", _("Import wurde durchgeführt."));
    }

    protected function removeErrorLines() {
        foreach ($this->db_tables as $db_table => $filename) {
            $class_name = $this->table_class[$db_table];
            $table = new $class_name($db_table);
            $table->removeErrorLines();
        }
    }

    public function checkEntry($db_table, $db_entry) {
        $class_name = $this->table_class[$db_table];
        $table = new $class_name($db_table);
        return $table->checkEntry($db_entry);
    }

    protected function getTableInfo() {
        $table_info = array();
        foreach ($this->db_tables as $db_table => $description) {
            $table_class = $this->table_class[$db_table];
            $table = new $table_class($db_table);
            $table_info[$db_table] = $table->getTableInfo();
        }
        return $table_info;
    }

    protected function handleUpload($table_name) {
        
        $db = DBManager::get();
        $db->exec("DROP TABLE IF EXISTS `".addslashes($table_name)."` ");

        $uploadfile = $GLOBALS['TMP_PATH'] . '/' . md5(uniqid('nfgggsddsgggdsfgggdxx',1));
        if (move_uploaded_file($_FILES[$table_name."_file"]['tmp_name'], $uploadfile)) {
            $output = CSVImportProcessor::getCSVDataFromFile($uploadfile);
        }

        $anzahl_zeilen = 0;

        if (count($output) > 1) {
            $this->createTable($table_name, $output);
        }
        $this->msg[] = array("success", sprintf(_("Upload erfolgreich! %s Zeilen wurden erkannt. Überprüfen Sie die Daten."), $anzahl_zeilen));
    }

    protected function createTable($table_name, $output) {
        $headline = array_shift($output);
        $table_class = $this->table_class[$table_name];
        $table = $table_class::createTable($table_name, $headline, $output);

        //Daten in die Tabelle schieben:
        $db = DBManager::get();
        foreach ($output as $line) {
            $insert_sql = "INSERT INTO `".addslashes($table_name)."` SET ";
            foreach ($headline as $key => $field) {
                $key < 1 || $insert_sql .= ", ";
                $value = isset($this->map_values[$table_name.".".$field])
                        ? call_user_func($this->map_values[$table_name.".".$field], trim($line[$key]))
                        : trim($line[$key]);
                $insert_sql .= "`".addslashes($field)."` = ".$db->quote($value)." ";
            }
            $anzahl_zeilen += $db->exec($insert_sql);
        }
    }

    protected function getTemplate($template_file_name, $layout = "without_infobox") {
        PageLayout::setTitle($this->getDisplayName());
        if (!$this->template_factory) {
            $this->template_factory = new Flexi_TemplateFactory(dirname(__file__)."/templates");
        }
        $template = $this->template_factory->open($template_file_name);
        if ($layout) {
            $template->set_layout($GLOBALS['template_factory']->open($layout === "without_infobox" ? 'layouts/base_without_infobox' : 'layouts/base'));
        }
        return $template;
    }

    protected function getDescription() {
        return _("Laden Sie zuerst die Datei hoch und im zweiten Schritt können Sie die Daten nochmal überprüfen.");
    }

    protected function getDisplayName() {
        return _("ImportPlugin");
    }

    protected function getSubmitInfo() {
        return null;
    }

}
