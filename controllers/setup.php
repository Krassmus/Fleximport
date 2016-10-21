<?php

require_once 'app/controllers/plugin_controller.php';

class SetupController extends PluginController {

    protected $utf8decode_xhr = true;

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        PageLayout::addScript($this->plugin->getPluginURL()."/assets/fleximport.js");
        Navigation::activateItem("/fleximport");
    }

    public function table_action($table_id = null)
    {
        PageLayout::setTitle($table_id ? _("Tabelleneinstellung bearbeiten") : _("Tabelle hinzufügen"));
        $this->table = new FleximportTable($table_id);
        if ($this->table->isNew() && Request::option("process_id")) {
            $this->table['process_id'] = Request::option("process_id");
        }
        Navigation::activateItem("/fleximport/process_".$this->table['process_id']);
        if (Request::isPost()) {
            $data = Request::getArray("table");
            $oldname = $this->table['name'];
            $data['tabledata'] = array_merge($this->table['tabledata'], $data['tabledata']);
            $data['synchronization'] = $data['synchronization'] ? 1 : 0;
            $this->table->setData($data);
            if ($oldname && $data['name'] && $oldname !== $data['name']) {
                try {
                    DBManager::get()->exec("RENAME TABLE `".addslashes($oldname)."`TO `".addslashes($data['name'])."`;");
                } catch(Exception $e) {}
            }
            if ($this->table['import_type'] === "other") {
                $this->table['import_type'] = Request::get("other_import_type");
            } elseif ($this->table['source'] === "sqlview" && $GLOBALS['perm']->have_perm("root")) {
                DBManager::get()->exec("DROP VIEW IF EXISTS `".addslashes($data['name'])."`");
                DBManager::get()->exec("DROP TABLE IF EXISTS `".addslashes($data['name'])."`");
                DBManager::get()->exec("
                    CREATE VIEW `".addslashes($data['name'])."` AS (
                        ".$data['tabledata']['sqlview']['select']."
                    );
                ");
            }
            $this->table->store();

            if (Request::isAjax()) {
                $output = array(
                    'func' => "STUDIP.Fleximport.updateTable",
                    'payload' => array(
                        'table_id' => $this->table->getId(),
                        'name' => $this->table['name'],
                        'html' => $this->render_template_as_string("import/_table.php")
                    )
                );
                $this->response->add_header("X-Dialog-Execute", json_encode(studip_utf8encode($output)));
            } else {
                PageLayout::postMessage(MessageBox::success(_("Daten wurden gespeichert.")));
            }
        }
    }

    public function removetable_action($table_id)
    {
        $this->table = new FleximportTable($table_id);
        $process_id = $this->table['process_id'];
        $this->table->drop();
        $this->table->delete();
        if (Request::isAjax()) {
            $this->render_nothing();
        } else {
            PageLayout::postMessage(MessageBox::success(_("Tabelle gelöscht.")));
            $this->redirect("import/overview/" . $process_id);
        }
    }

    public function tablemapping_action($table_id)
    {
        PageLayout::setTitle(_("Datenmapping einstellen"));
        $this->table = new FleximportTable($table_id);
        Navigation::activateItem("/fleximport/process_".$this->table['process_id']);
        if (Request::isPost()) {
            $tabledata = Request::getArray("tabledata");
            $tabledata = array_merge($this->table['tabledata'], $tabledata);
            $this->table['tabledata'] = $tabledata;
            $this->table->store();
            PageLayout::postMessage(MessageBox::success(_("Daten wurden gespeichert.")));
        }
        $datafield_object_types = array(
            'User' => "user",
            'Course' => "sem",
            'CourseMember' => "usersemdata"
        );
        $this->datafields = Datafield::findBySQL("object_type = :object_type", array(
            'object_type' => $datafield_object_types[$this->table['import_type']]
        ));
        if (Request::isAjax() && Request::isPost()) {
            $output = array(
                'func' => "STUDIP.Fleximport.updateTable",
                'payload' => array(
                    'table_id' => $table_id,
                    'name' => $this->table['name'],
                    'html' => $this->render_template_as_string("import/_table.php")
                )
            );
            $this->response->add_header("X-Dialog-Execute", json_encode(studip_utf8encode($output)));
        }
    }

}