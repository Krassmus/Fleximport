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

}