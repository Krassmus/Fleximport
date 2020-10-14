<? if ($process) : ?>
    <form action="<?= PluginEngine::getLink($plugin, array(), "import/process/".$process->getId()) ?>"
          method="post"
          enctype="multipart/form-data"
          id="process_form">
        <? foreach ($tables as $table) : ?>
            <? if ($table['active']) : ?>
            <?= $this->render_partial("import/_table.php", array('table' => $table)) ?>
            <? endif ?>
        <? endforeach ?>
    </form>

    <div style="text-align: center;">
        <?
        $needsFetching = false;
        foreach ($tables as $table) {
            if ($table->needsFetching()) {
                $needsFetching = true;
                break;
            }
        }
        ?>
        <? if ($needsFetching) : ?>
        <?= \Studip\LinkButton::create(_("Daten abrufen"), PluginEngine::getURL($plugin, array(), "import/processfetch/".$process->getId())) ?>
        <? endif ?>
        <?= \Studip\Button::create(_("Import starten"), 'start', array(
            'onClick' => "return window.confirm('"._("Wirklich importieren?")."');",
            'form' => "process_form"
        )) ?>
    </div>
<? endif ?>

<?
$actions = new ActionsWidget();
if (!FleximportConfig::get("DISALLOW_ADMINISTRATION")) {
    $actions->addLink(
        _("Prozess erstellen"),
        PluginEngine::getURL($plugin, array(), "process/edit"),
        Icon::create("archive2", "clickable"),
        ['data-dialog' => 1]
    );
}
if ($process) {
    if (!FleximportConfig::get("DISALLOW_ADMINISTRATION")) {
        $actions->addLink(
            _("Prozess bearbeiten"),
            PluginEngine::getURL($plugin, array(), "process/edit/" . $process->getId()),
            Icon::create("edit", "clickable"),
            ['data-dialog' => 1]
        );
        $actions->addLink(
            _("Prozess duplizieren"),
            PluginEngine::getURL($plugin, array(), "process/duplicate/" . $process->getId()),
            Icon::create("wizard", "clickable"),
            ['data-dialog' => 1]
        );
        $actions->addLink(
            _("Tabelle hinzufügen"),
            PluginEngine::getURL($plugin, array('process_id' => $process->getId()), "setup/table"),
            Icon::create("add", "clickable"),
            ['data-dialog' => 1]
        );
    }
    if ($needsFetching) {
        $actions->addLink(
            _("Daten abrufen"),
            PluginEngine::getURL($plugin, array(), "import/processfetch/".$process->getId()),
            Icon::create("arr_1down", "clickable")
        );
    }
    $actions->addLink(
        _("Prozess exportieren"),
        PluginEngine::getURL($plugin, array(), "process/export/".$process->getId()),
        Icon::create("export", "clickable"),
        [
            'title' => _("Exportiert diesen Prozess in eine Konfigurationsdatei, die man in einem anderem Stud.IP wieder importieren kann.")
        ]
    );


}
if (!FleximportConfig::get("DISALLOW_ADMINISTRATION")) {
    $actions->addLink(
        _("Prozess importieren"),
        PluginEngine::getURL($plugin, array(), "process/import"),
        Icon::create("upload", "clickable"),
        ['data-dialog' => 1]
    );
}

Sidebar::Get()->addWidget($actions);
