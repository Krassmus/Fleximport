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
                $this->process->setData(Request::getArray("data"));
                $this->process->store();
                PageLayout::postMessage(MessageBox::success(_("Prozess wurde gespeichert")));
                $this->redirect("import/overview/" . $this->process->getId());
            }
        }
    }
}