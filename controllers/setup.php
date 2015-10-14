<?php

require_once 'app/controllers/plugin_controller.php';

class SetupController extends PluginController {

    public function table_action($table_id = null)
    {
        PageLayout::setTitle($table_id ? _("Tabelleneinstellung bearbeiten") : _("Tabelle hinzufügen"));
        $this->table = new FleximportTable($table_id);
        if (Request::isPost()) {
            $this->table->setData(Request::getArray("table"));
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
        $this->table = new FleximportTable($table_id);
        $object_types = array(
            'user' => "user",
            'course' => "sem",
            'member' => "usersemdata"
        );
        $this->datafields = Datafield::findBySQL("object_type = :object_type", array(
            'object_type' => $object_types[$this->table['import_type']]
        ));
    }

}