<?php

require_once 'app/controllers/plugin_controller.php';

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
    }
}
