<?php

require_once 'app/controllers/plugin_controller.php';

class ImportController extends PluginController {

    public function overview_action()
    {
        $this->tables = FleximportTable::findAll();
    }

    public function process_action()
    {
        if (Request::isPost()) {
            if (Request::submitted("start")) {
                $protocol = array();
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

}