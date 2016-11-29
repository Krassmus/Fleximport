<? if ($process) : ?>
    <form action="<?= PluginEngine::getLink($plugin, array(), "import/process/".$process->getId()) ?>"
          method="post"
          enctype="multipart/form-data"
          id="process_form">
        <? foreach ($tables as $table) : ?>
            <?= $this->render_partial("import/_table.php", array('table' => $table)) ?>
        <? endforeach ?>

    </form>

    <div style="text-align: center;">
        <?= \Studip\Button::create(_("Import starten"), 'start', array(
            'onClick' => "return window.confirm('"._("Wirklich importieren?")."');",
            'form' => "process_form"
        )) ?>
    </div>
<? endif ?>

<?
$actions = new ActionsWidget();
$actions->addLink(
    _("Prozess erstellen"),
    PluginEngine::getURL($plugin, array(), "process/edit"),
    version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
        ? Icon::create("archive2", "info")
        : Assets::image_path("icons/black/add/archive2"),
    array('data-dialog' => 1)
);
if ($process) {
    $actions->addLink(
        _("Prozess bearbeiten"),
        PluginEngine::getURL($plugin, array(), "process/edit/".$process->getId()),
        version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
            ? Icon::create("edit", "info")
            : Assets::image_path("icons/black/edit"),
        array('data-dialog' => 1)
    );
    $actions->addLink(
        _("Tabelle hinzufügen"),
        PluginEngine::getURL($plugin, array('process_id' => $process->getId()), "setup/table"),
        version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
            ? Icon::create("add", "info")
            : Assets::image_path("icons/black/add"),
        array('data-dialog' => 1)
    );
}

Sidebar::Get()->addWidget($actions);