<form class="<?= Fleximport::getCSSFormClass() ?>" action="<?= PluginEngine::getLink($plugin, array(), "process/edit".($process->isNew() ? "" : "/".$process->getId())) ?>" method="post">
    <label>
        <?= _("Name") ?>
        <input type="text" name="data[name]" value="<?= htmlReady($process['name']) ?>" required placeholder="<?= _("Prozessbezeichnung") ?>">
    </label>

    <label>
        <?= _("Beschreibung (optional)") ?>
        <textarea name="data[description]"><?= htmlReady($process['description']) ?></textarea>
    </label>

    <label>
        <input type="checkbox" value="1" name="data[triggered_by_cronjob]"<?= $process['triggered_by_cronjob'] ? " checked" : "" ?>>
        <?= _("Durch Cronjob starten") ?>
    </label>

    <label>
        <input type="checkbox" value="1" name="data[webhookable]"<?= $process['webhookable'] ? " checked" : "" ?> onChange="jQuery('#process_webhook_url').toggle(this.checked);">
        <?= _("Durch Webhook starten") ?>
    </label>

    <? if (!$process->isNew()) : ?>
        <?
        $old_base = URLHelper::setBaseURL($GLOBALS['ABSOLUTE_URI_STUDIP']);
        $link = PluginEngine::getLink($plugin, array(), "webhookendpoints/update/".$process->getId());
        URLHelper::setBaseURL($old_base);
        ?>
        <div id="process_webhook_url" style="<?= $process['webhookable'] ? "" : "display: none;"?>">
            <input type="text" value="<?= $link ?>">
        </div>
    <? endif ?>

    <div data-dialog-button>
        <?= \Studip\Button::create(_("Speichern")) ?>
        <? if (!$process->isNew()) : ?>
            <?= \Studip\Button::create(_("Löschen"), "delete_process", array('onclick' => "return window.confirm('"._("Wirklich löschen?")."');")) ?>
        <? endif ?>
    </div>
</form>