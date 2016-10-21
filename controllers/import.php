<?php

require_once 'app/controllers/plugin_controller.php';

class ImportController extends PluginController {

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        if (FleximportConfig::get("MAXIMUM_EXECUTION_TIME")) {
            set_time_limit(FleximportConfig::get("MAXIMUM_EXECUTION_TIME"));
        }
        PageLayout::addScript($this->plugin->getPluginURL()."/assets/fleximport.js");
        Navigation::activateItem("/fleximport");
    }

    public function overview_action($process_id = null)
    {
        $this->process = FleximportProcess::find($process_id);
        if ($this->process) {
            foreach ($this->process->tables as $table) {
                $table->fetchData();
            }
            Navigation::activateItem("/fleximport/process_".$process_id);
            $this->tables = $this->process->tables;
            if ($this->process['description']) {
                PageLayout::postMessage(MessageBox::info($this->process['description']));
            }
        } else {
            Navigation::activateItem("/fleximport/overview");
            PageLayout::postMessage(MessageBox::info(_("Erstellen Sie erst einen Prozess und dann darin die Tabellen, die importiert werden sollen.")));
        }
    }

    public function process_action($process_id)
    {
        if (Request::isPost()) {
            if (Request::submitted("start")) {
                $protocol = array();
                $starttime = time();
                $this->process = FleximportProcess::find($process_id);
                $this->tables = $this->process->tables;
                foreach ($this->tables as $table) {
                    $table->fetchData();
                }
                foreach ($this->tables as $table) {
                    $table->doImport();
                }
                $duration = time() - $starttime;
                if ($duration >= 60) {
                    PageLayout::postMessage(MessageBox::success(sprintf(_("Import wurde durchgeführt und dauerte %s Minuten"), floor($duration / 60)), $protocol));
                } else {
                    PageLayout::postMessage(MessageBox::success(_("Import wurde durchgeführt"), $protocol));
                }

            } elseif ($_FILES['tableupload']) {
                foreach ($_FILES['tableupload']['tmp_name'] as $table_id => $tmp_name) {
                    if ($tmp_name) {
                        $table = new FleximportTable($table_id);
                        $output = $this->plugin->getCSVDataFromFile($tmp_name);
                        if ($table['tabledata']['source_encoding'] === "utf8") {
                            $output = studip_utf8decode($output);
                        }
                        $headline = array_shift($output);
                        $table->createTable($headline, $output);
                    }
                }
                PageLayout::postMessage(MessageBox::success(_("CSV-Datei hochgeladen")));
            }

        }
        $this->redirect("import/overview/".$process_id);
    }

    public function showtable_action($table_id)
    {
        $this->table = new FleximportTable($table_id);
        $this->limit = false;
        $this->set_layout(null);
        $this->render_template("import/_table.php");
    }

    public function targetdetails_action($id)
    {
        $this->table = FleximportTable::findOneByName(Request::get("table"));
        $this->line = $this->table->getLine($id);
        $this->data = $this->table->getMappedData($this->line);

        $pk = $this->table->getPrimaryKey($this->data);
        $classname = $this->table['import_type'];
        if ($classname) {
            $this->object = new $classname($pk);
        }
    }

}