<?php

require_once 'app/controllers/plugin_controller.php';

class ExportController extends PluginController {

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        if (FleximportConfig::get("MAXIMUM_EXECUTION_TIME")) {
            set_time_limit(FleximportConfig::get("MAXIMUM_EXECUTION_TIME"));
        }
    }

    public function export_action($id)
    {
        $this->table = FleximportTable::find($id);
        if ((Request::option("secret") !== $this->table->getExportSecret())) {
            throw new AccessDeniedException();
        }
        $this->table->fetchData();
        $this->render_table_as_csv();
    }

    protected function render_table_as_csv()
    {
        $output = array();
        $header = $this->table->getTableHeader();
        array_shift($header);
        //remove import_table_primary_key

        $output[] = $header;
        foreach ($this->table->getLines() as $line) {
            $output_line = array();
            foreach ($header as $field) {
                $output_line[] = $line[$field];
            }
            $output[] = $output_line;
        }
        $delimiter = ";";
        $mask = '"';
        $output_string = chr(239) . chr(187) . chr(191); //BOM
        foreach ($output as $key => $line) {
            if ($key > 0) {
                $output_string .= "\n";
            }
            foreach ($line as $key2 => $value) {
                if ($key2 > 0) {
                    $output_string .= $delimiter;
                }
                $output_string .= $mask.str_replace(array($mask, "\n"), array($mask.$mask, "\\n"), $value).$mask;
            }
        }
        $this->response->add_header("Content-Type", "text/csv");
        $this->response->add_header("Content-Disposition", "Attachment; filename=".$this->table->name.".csv");
        $this->render_text($output_string);
    }

}
