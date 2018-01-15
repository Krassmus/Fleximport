<?php

require_once 'app/controllers/plugin_controller.php';

class WebhookendpointsController extends PluginController {

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        if (FleximportConfig::get("MAXIMUM_EXECUTION_TIME")) {
            set_time_limit(FleximportConfig::get("MAXIMUM_EXECUTION_TIME"));
        }
    }

    public function update_action($process_id) {
        $this->process = new FleximportProcess($process_id);
        if (Request::isPost() && $this->process['webhookable']) {
            foreach ($this->process->tables as $table) {
                //import data if needed
                $table->fetchData();
            }
            foreach ($this->process->tables as $table) {
                $table->doImport();
            }
        }
        $this->render_text("ok");
    }

    public function pushupdate_action($table_id, $cmd = "update") {
        $table = FleximportTable::find($table_id);
        if (Request::isPost() && $table && $table['pushupdate']) {
            $body = file_get_contents('php://input');
            if ($table->getPlugin()) {
                $body = $table->getPlugin()->fetchPushData($body, $_SERVER['HTTP_CONTENT_TYPE']);
            }
            if (!is_array($body)) {
                //internal logging
                switch ($_SERVER['HTTP_CONTENT_TYPE']) {
                    case "text/json":
                    case "application/json":
                        $body = json_decode($body);
                        if (is_array($body) && !$this->isAssoc($body)) {
                            $datalines = $body;
                        } else {
                            $datalines = array($body);
                        }
                        break;
                    case "text/csv":
                    default:
                        //CSV to data:
                        $type = "csv";
                        if ($table['tabledata']['source_encoding'] !== "utf8") {
                            $body = mb_convert_encoding($body, "UTF-8", "Windows-1252");
                        }
                        $body = $table->CSV2Array($body);
                        $headline = array_shift($body);
                        foreach ($body as $bodyline) {
                            $line = array();
                            foreach ($headline as $index => $field) {
                                $line[$field] = $bodyline[$index];
                            }
                            $datalines[] = $line;
                        }
                        break;
                }
            } else {
                //plugin did that processing:
                $datalines = $body;
            }
            //now we have the data and do the dirty work:
            foreach ($datalines as $line) {
                if ($cmd === "update") {
                    $table->importLine($line);

                    if ($table['synchronization']) {
                        $data = $table->getMappedData($line);
                        $pk = $table->getPrimaryKey($data);
                        $item_id = implode("-", $pk);

                        $mapped = FleximportMappedItem::findbyItemId($item_id, $table->getId()) ?: new FleximportMappedItem();
                        $mapped['table_id'] = $table->getId();
                        $mapped['item_id'] = $item_id;
                        $mapped['chdate'] = time();
                        $mapped->store();
                    }
                } else {
                    //delete
                    $classname = $table['import_type'];
                    $data = $table->getMappedData($line);
                    $pk = $table->getPrimaryKey($data);
                    $object = new $classname($pk);
                    if ($table['import_type'] === "User") {
                        StudipLog::log(
                            "USER_DEL",
                            $object->getId(),
                            NULL,
                            "Durch Fleximport-Push-DELETE der Tabelle ".$table['name'].": " . join(';', $object->toArray('username vorname nachname perms email'))
                        );
                    }
                    $object->delete();

                    if ($table['synchronization']) {
                        $item_id = implode("-", $pk);
                        $mapped = FleximportMappedItem::findbyItemId($item_id, $table->getId()) ?: new FleximportMappedItem();
                        if ($mapped) {
                            $mapped->delete();
                        }
                    }
                }
            }
        }
    }

    public function pushdelete_action($table_id, $cmd = "delete") {
        $this->pushdelete_action($table_id, $cmd);
    }

    protected function isAssoc(array $arr)
    {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

}