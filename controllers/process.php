<?php

class ProcessController extends PluginController {

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        if (!$GLOBALS['perm']->have_perm("root")) {
            throw new AccessDeniedException();
        }
        PageLayout::addScript($this->plugin->getPluginURL()."/assets/fleximport.js");
        Navigation::activateItem("/fleximport/config");
    }

    public function edit_action($process_id = null)
    {
        $this->process = new FleximportProcess($process_id);
        if (Request::isPost()) {
            if (Request::submitted("delete_process")) {
                $this->process->delete();
                PageLayout::postMessage(MessageBox::success(_("Prozess wurde gelöscht.")));
                $processes = FleximportProcess::findBySQL("1=1 ORDER BY name ASC");
                $this->redirect("import/overview" . (count($processes) ? "/".$processes[0]['process_id'] : ""));
            } else {
                $data = Request::getArray("data");
                $data['triggered_by_cronjob'] = $data['triggered_by_cronjob'] ? 1 : 0;
                $data['webhookable'] = $data['webhookable'] ? 1 : 0;
                $this->process->setData($data);
                $this->process->store();
                foreach ((array) Request::getArray("configs") as $config_name => $value) {
                    $this->process->setConfig($config_name, $value);
                }
                $activetables = Request::getArray("activetable");
                foreach ($this->process->tables as $table) {
                    $table['active'] = $activetables[$table->getId()];
                    $table->store();
                }
                PageLayout::postMessage(MessageBox::success(_("Prozess wurde gespeichert")));
                $this->redirect("import/overview/" . $this->process->getId());
            }
        }
        $schedules = CronjobSchedule::findBySQL("INNER JOIN cronjobs_tasks USING (task_id) WHERE cronjobs_tasks.`class` = 'FleximportJob'");
        $this->charges = [];
        foreach ($schedules as $schedule) {
            if ($schedule->parameters['charge']) {
                $this->charges[] = $schedule->parameters['charge'];
            }
        }
        $this->charges = array_unique($this->charges);
        $this->neededConfigs = [];
        foreach ($this->process->tables as $table) {
            $this->neededConfigs = array_merge($this->neededConfigs, $table->neededProcessConfigs());
        }
        $this->neededConfigs = array_unique($this->neededConfigs);
    }

    public function duplicate_action($process_id)
    {
        $this->process = new FleximportProcess($process_id);
        if (Request::isPost()) {
            $new_process = new FleximportProcess();
            $data = Request::getArray("data");
            $data['triggered_by_cronjob'] = $data['triggered_by_cronjob'] ? 1 : 0;
            $data['webhookable'] = $data['webhookable'] ? 1 : 0;
            $new_process->setData($data);
            $new_process->store();
            foreach ((array) Request::getArray("configs") as $config_name => $value) {
                $new_process->setConfig($config_name, $value);
            }
            foreach ($this->process->tables as $table) {
                $new_table = new FleximportTable();
                $new_table->setData($table->toRawArray());
                $new_table->setId($new_table->getNewId());
                $new_table['process_id'] = $new_process->getId();
                $new_table->store();
            }
            PageLayout::postMessage(MessageBox::success(_("Prozess wurde dupliziert")));
            $this->redirect("import/overview/" . $new_process->getId());
            return;
        }
        $this->direction = "duplicate";
        $schedules = CronjobSchedule::findBySQL("INNER JOIN cronjobs_tasks USING (task_id) WHERE cronjobs_tasks.`class` = 'FleximportJob'");
        $this->charges = [];
        foreach ($schedules as $schedule) {
            if ($schedule->parameters['charge']) {
                $this->charges[] = $schedule->parameters['charge'];
            }
        }
        $this->charges = array_unique($this->charges);
        $this->neededConfigs = [];
        foreach ($this->process->tables as $table) {
            $this->neededConfigs = array_merge($this->neededConfigs, $table->neededProcessConfigs());
        }
        $this->neededConfigs = array_unique($this->neededConfigs);
        $this->render_action("edit");
    }

    public function export_action($process_id)
    {
        $this->process = new FleximportProcess($process_id);
        $output = [
            'process' => $this->process->toRawArray(),
            'tables' => []
        ];
        unset($output['process']['process_id']);
        unset($output['process']['mkdate']);
        unset($output['process']['chdate']);
        unset($output['process']['last_data_import']);

        foreach ($this->process->tables as $table) {
            $tabledata = $table->toRawArray();
            unset($tabledata['table_id']);
            unset($tabledata['process_id']);
            unset($tabledata['mkdate']);
            unset($tabledata['chdate']);
            $tabledata['tabledata'] = $table->tabledata;
            $output['tables'][] = $tabledata;
        }

        $this->response->add_header("Content-Disposition", 'attachment; filename="'.$this->process['name'].'.flxip"');
        $this->render_json($output);
    }

    /**
     * Imports a new process by a flxip (fleximport process) file
     */
    public function import_action()
    {
        if (Request::isPost()) {
            if (file_exists($_FILES['file']['tmp_name'])) {
                $file = json_decode(file_get_contents($_FILES['file']['tmp_name']), true);

                $process = new FleximportProcess();
                $process->setData($file['process']);
                $process->store();
                foreach ($file['tables'] as $tabledata) {
                    $table = new FleximportTable();
                    $table->setData($tabledata);
                    $table['process_id'] = $process->getId();
                    $table->store();
                }
                PageLayout::postSuccess(sprintf(_("Prozess %s wurde angelegt."), $process['name']));
                $this->redirect("import/overview/".$process->getId());
            }
        }
    }
}
