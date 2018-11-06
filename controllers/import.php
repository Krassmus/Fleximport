<?php

require_once 'app/controllers/plugin_controller.php';

class ImportController extends PluginController {

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        if (!$GLOBALS['perm']->have_perm("root")) {
            throw new AccessDeniedException();
        }
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
            if (!$this->process['cache_tables']
                    || (time() - $this->process['last_data_import'] * 60 > $this->process['cache_tables'])) {
                foreach ($this->process->tables as $table) {
                    $table->fetchData();
                }
                foreach ($this->process->tables as $table) {
                    $table->afterDataFetching();
                }
                $this->process['last_data_import'] = time();
                $this->process->store();
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
                        if ($table['tabledata']['source_encoding'] !== "utf8") {
                            $output = $this->mb_convert_encoding_rec($output, "UTF-8", "Windows-1252");
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

    protected function mb_convert_encoding_rec($input, $to_enc, $from_enc)
    {
        if (is_string($input)) {
            return mb_convert_encoding($input, $to_enc, $from_enc);
        } elseif(is_array($input)) {
            $new = array();
            foreach ($input as $key => $value) {
                $new[mb_convert_encoding($key, $to_enc, $from_enc)] = $this->mb_convert_encoding_rec($value, $to_enc, $from_enc);
            }
            return $new;
        }
        return mb_convert_encoding((string) $input, $to_enc, $from_enc);
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
        $this->additional_fields = array();
        foreach (get_declared_classes() as $class) {
            $reflection = new ReflectionClass($class);
            if ($reflection->implementsInterface('FleximportDynamic') && ($class !== "FleximportDynamic")) {
                $dynamic = new $class();
                $for = $dynamic->forClassFields();
                foreach ((array) $for[$classname] as $fieldname => $placeholder) {
                    if ($this->table['tabledata']['simplematching'][$fieldname]['column']) {
                        $this->additional_fields[$fieldname] = method_exists($dynamic, "currentValue")
                            ? $dynamic->currentValue($this->object, $fieldname, (bool) $this->table['tabledata']['simplematching'][$fieldname]['sync'])
                            : false;
                    }
                }
            }
        }
        $this->datafields = array();
        switch ($classname) {
            case "Course":
                $this->datafields = DataField::findBySQL("object_type = 'sem'");
                break;
            case "User":
                $this->datafields = DataField::findBySQL("object_type = 'user'");
                break;
            case "CourseMember":
                $this->datafields = DataField::findBySQL("object_type = 'usersemdata'");
                break;
        }
    }

    public function deletables_action($table_id)
    {
        $this->table = FleximportTable::find($table_id);
        $this->class = $this->table['import_type'];
        $item_ids = array();
        foreach ($this->table->fetchLines() as $line) {
            $report = $this->table->checkLine($line);
            if ($report['pk'] && !$report['errors']) {
                $item_ids[] = is_array($report['pk']) ? implode("-", $report['pk']) : $report['pk'];
            }
        }
        $this->deletables = $this->table->findDeletableItems($item_ids);
        PageLayout::setTitle(_("Zu löschende Datensätze"));
    }

}