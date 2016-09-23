<?php

require_once 'app/controllers/plugin_controller.php';

class ProcessController extends PluginController {

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        Navigation::activateItem("/fleximport/config");
    }

    public function edit_action($process_id = null)
    {
        $this->process = new FleximportProcess($process_id);
        if (Request::isPost()) {
            $this->process->setData(Request::getArray("data"));
            $this->process->store();
            PageLayout::postMessage(MessageBox::success(_("Prozess wurde gespeichert")));
            $this->redirect("import/overview/".$this->process->getId());
        }
    }
}