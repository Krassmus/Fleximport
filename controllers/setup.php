<?php

require_once 'app/controllers/plugin_controller.php';

class SetupController extends PluginController {

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        Navigation::activateItem("/fleximport/overview");
    }

    public function table_action($table_id = null)
    {
        PageLayout::setTitle($table_id ? _("Tabelleneinstellung bearbeiten") : _("Tabelle hinzufügen"));
        $this->table = new FleximportTable($table_id);
        if (Request::isPost()) {
            $data = Request::getArray("table");
            $data['tabledata'] = array_merge($this->table['tabledata'], $data['tabledata']);
            $data['synchronization'] = $data['synchronization'] ? 1 : 0;
            $this->table->setData($data);
            if ($this->table['import_type'] === "other") {
                $this->table['import_type'] = Request::get("other_import_type");
            }
            $this->table->store();
            PageLayout::postMessage(MessageBox::success(_("Daten wurden gespeichert.")));
        }
    }

    public function removetable_action($table_id)
    {
        $this->table = new FleximportTable($table_id);
        $this->table->drop();
        $this->table->delete();
        PageLayout::postMessage(MessageBox::success(_("Tabelle gelöscht.")));
        $this->redirect("import/overview");
    }

    public function tablemapping_action($table_id)
    {
        PageLayout::setTitle(_("Datenmapping einstellen"));
        $this->table = new FleximportTable($table_id);
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
    }

}