<?php

require_once 'app/controllers/plugin_controller.php';

class ConfigController extends PluginController {

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        if (!$GLOBALS['perm']->have_perm("root")) {
            throw new AccessDeniedException();
        }
        Navigation::activateItem("/fleximport/config");
    }

    public function overview_action()
    {
        PageLayout::addScript($this->plugin->getPluginURL()."/assets/jquery.elastic.source.js");
        $this->configs = FleximportConfig::all();
        $this->possibleConfigs = array();
        foreach (FleximportTable::findAll() as $table) {
            $this->possibleConfigs = array_merge($this->possibleConfigs, $table->neededConfigs());
        }
        $this->possibleConfigs = array_unique($this->possibleConfigs);
    }

    public function edit_action()
    {
        if (Request::isPost()) {
            $configs = Request::getArray("configs");
            foreach ($configs as $name => $data) {
                if ($name !== $data['name'] || !$data['value']) {
                    FleximportConfig::delete($name);
                }
                if ($data['name'] && $data['value']) {
                    FleximportConfig::set($data['name'], $data['value']);
                }
            }
            if (Request::get("new_name") && Request::get("new_value")) {
                FleximportConfig::set(Request::get("new_name"), Request::get("new_value"));
            }
        }
        $this->redirect("config/overview");
    }
}