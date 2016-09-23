<form action="<?= PluginEngine::getLink($plugin, array(), "import/process/".$process->getId()) ?>"
      method="post"
      enctype="multipart/form-data">

    <? if ($process) : ?>
        <? foreach ($tables as $table) : ?>
            <? if ($table->isInDatabase()) : ?>
                <div id="table_<?= $table->getId() ?>_container">
                    <?= $this->render_partial("import/_table.php", compact("table")) ?>
                </div>
            <? else : ?>
                <div style="margin-bottom: 50px;">
                    <div style="float: right;">
                        <a href="<?= PluginEngine::getLink($plugin, array(), "setup/table/".$table->getId()) ?>" data-dialog>
                            <?= Assets::img("icons/20/blue/admin") ?>
                        </a>
                        <a href="<?= PluginEngine::getLink($plugin, array(), "setup/removetable/".$table->getId()) ?>" onClick="return window.confirm('<?= _("Wirklich löschen?") ?>');">
                            <?= Assets::img("icons/20/blue/trash") ?>
                        </a>
                    </div>
                    <h2>
                        <? switch ($table['import_type']) {
                            case "User":
                                echo Assets::img("icons/20/black/person", array('class' => "text-bottom"));
                                break;
                            case "CourseMember":
                                echo Assets::img("icons/20/black/group2", array('class' => "text-bottom"));
                                break;
                            case "Course":
                                echo Assets::img("icons/20/black/seminar", array('class' => "text-bottom"));
                                break;
                            case "":
                                echo Assets::img("icons/20/black/remove-circle", array('class' => "text-bottom"));
                                break;
                            default:
                                echo Assets::img("icons/20/black/doit", array('class' => "text-bottom", 'title' => $table['import_type'] ? sprintf(_("Es werden %s-Objekte importiert."), $table['import_type']) : _("Hilfstabelle wird nicht direkt importiert.")));
                                break;
                        } ?>
                        <?= htmlReady($table['name']) ?>
                    </h2>

                    <? if ($table['source'] === "csv_upload") : ?>
                        <label style="cursor: pointer;">
                            <?= Assets::img("icons/40/blue/upload", array('class' => "text-bottom")) ?>
                            <?= _("CSV-Datei hochladen") ?>
                            <input type="file" name="tableupload[<?= $table->getId() ?>]" onChange="jQuery(this).closest('form').submit();" style="display: none;">
                        </label>
                    <? endif ?>
                </div>
            <? endif ?>
        <? endforeach ?>

        <div style="text-align: center;">
            <?= \Studip\Button::create(_("Import starten"), 'start', array('onClick' => "return window.confirm('"._("Wirklich importieren?")."');")) ?>
        </div>
    <? endif ?>

</form>

<?

$actions = new ActionsWidget();
$actions->addLink(
    _("Prozess erstellen"),
    PluginEngine::getURL($plugin, array(), "process/edit"),
    Assets::image_path("icons/black/add"),
    array('data-dialog' => 1)
);
if ($process) {
    $actions->addLink(
        _("Tabelle hinzufügen"),
        PluginEngine::getURL($plugin, array('process_id' => $process->getId()), "setup/table"),
        Assets::image_path("icons/black/add"),
        array('data-dialog' => 1)
    );
}

Sidebar::Get()->addWidget($actions);