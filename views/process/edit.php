<form class="default" action="<?= PluginEngine::getLink($plugin, array(), "process/edit".($process->isNew() ? "" : "/".$process->getId())) ?>" method="post">
    <label>
        <?= _("Name") ?>
        <input type="text" name="data[name]" value="<?= htmlReady($process['name']) ?>" required placeholder="<?= _("Prozessbezeichnung") ?>">
    </label>

    <label>
        <input type="checkbox" value="1" name="data[triggered_by_cronjob]"<?= $process['triggered_by_cronjob'] ? " checked" : "" ?>>
        <?= _("Durch Cronjob starten") ?>
    </label>

    <div data-dialog-button>
        <?= \Studip\Button::create(_("Speichern")) ?>
    </div>
</form>