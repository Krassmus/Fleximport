<?php

require_once 'app/controllers/plugin_controller.php';

class ImportController extends PluginController {

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        Navigation::activateItem("/fleximport/overview");
    }

    public function overview_action()
    {
        $this->tables = FleximportTable::findAll();
    }

    public function process_action()
    {
        if (Request::isPost()) {
            if (Request::submitted("start")) {
                $protocol = array();
                $this->tables = FleximportTable::findAll();
                foreach ($this->tables as $table) {
                    $table->doImport();
                }
                PageLayout::postMessage(MessageBox::success(_("Import wurde durchgeführt"), $protocol));
            } elseif ($_FILES['tableupload']) {
                foreach ($_FILES['tableupload']['tmp_name'] as $table_id => $tmp_name) {
                    $table = new FleximportTable($table_id);
                    $output = $this->plugin->getCSVDataFromFile($tmp_name);
                    $headline = array_shift($output);
                    $table->createTable($headline, $output);
                }
                PageLayout::postMessage(MessageBox::success(_("CSV-Datei hochgeladen")));
            }

        }
        $this->redirect("import/overview");
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