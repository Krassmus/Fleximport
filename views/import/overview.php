<? if ($process) : ?>
    <form action="<?= PluginEngine::getLink($plugin, array(), "import/process/".$process->getId()) ?>"
          method="post"
          enctype="multipart/form-data"
          data-process_id="<?= htmlReady($process->getId()) ?>"
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
        $fetchduration = 0;
        $importduration = 0;
        foreach ($tables as $table) {
            if ($table['active']) {
                if ($table->needsFetching()) {
                    $needsFetching = true;
                }
                if ($table['last_fetch_duration'] != -1) {
                    $fetchduration += $table['last_fetch_duration'];
                }
                if ($table['last_import_duration'] != -1) {
                    $importduration += $table['last_import_duration'];
                }
            }
        }
        ?>
        <? if ($needsFetching) : ?>
            <?= \Studip\LinkButton::create(_("Daten abrufen"), PluginEngine::getURL($plugin, array(), "import/processfetch/".$process->getId()), [
                'onClick' => "return STUDIP.Fleximport.showProgress.call(this);",
                'data-duration' => $fetchduration
            ]) ?>
        <? endif ?>
        <?= \Studip\Button::create(_("Import starten"), 'start', [
            'onClick' => "let confirm = window.confirm('"._("Wirklich importieren?")."'); if (confirm) { STUDIP.Fleximport.showProgress.call(this); } return confirm;",
            'form' => "process_form",
            'data-duration' => $fetchduration + $importduration
        ]) ?>
    </div>

    <div id="waiting_window"
         style="display: none;"
         data-title_process="<?= _("Prozess wird import ...") ?>"
         data-title_fetch="<?= _("Daten werden abgerufen ...") ?>">
        <div class="bar"></div>

        <dl>
            <dt><?= _("Aktuelle Dauer") ?></dt>
            <dd class="recent"></dd>
            <dt><?= _("Dauer der letzten Ausführung") ?></dt>
            <dd class="last"></dd>
        </dl>
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
            Icon::create("arr_1down", "clickable"),
            [
                'onClick' => "STUDIP.Fleximport.showProgress.call(this);",
                'data-duration' => $fetchduration
            ]
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
