<form class="<?= Fleximport::getCSSFormClass() ?>"
      action="<?= PluginEngine::getLink($plugin, array(), "process/".($direction ?: "edit").($process->isNew() ? "" : "/".$process->getId())) ?>"
      method="post">

    <fieldset>
        <legend><?= _("Basiseinstellungen des Prozess") ?></legend>

        <label>
            <?= _("Name") ?>
            <input type="text" name="data[name]" value="<?= htmlReady($process['name']) ?>" required placeholder="<?= _("Prozessbezeichnung") ?>">
        </label>

        <label>
            <?= _("Beschreibung (optional)") ?>
            <textarea name="data[description]"><?= htmlReady($process['description']) ?></textarea>
        </label>

        <label>
            <input type="checkbox"
                   value="1"
                   onChange="$('#edit_charge').toggle('fade');"
                   name="data[triggered_by_cronjob]"<?= $process['triggered_by_cronjob'] ? " checked" : "" ?>>
            <?= _("Durch Cronjob starten") ?>
        </label>

        <label id="edit_charge"<?= $process['triggered_by_cronjob'] ? "" : ' style="display: none;"' ?>>
            <?= _("Charge der Cronjob-Prozesse") ?>
            <select name="data[charge]">
                <option value=""></option>
                <option value="cli"<?= $process['charge'] == "cli" ? " selected": "" ?> title="<?= _("Relevant für das Skript import.cli.php, das im Ordner des Plugins liegt.") ?>">cli</option>
                <? foreach ($charges as $charge) : ?>
                    <option value="<?= htmlReady($charge) ?>"<?= $process['charge'] == $charge ? " selected": "" ?>>
                        <?= htmlReady($charge) ?>
                    </option>
                <? endforeach ?>
            </select>
        </label>

        <label>
            <input type="checkbox"
                   value="1"
                   name="data[webhookable]"<?= $process['webhookable'] ? " checked" : "" ?> onChange="jQuery('#process_webhook_url').toggle(this.checked);">
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
    </fieldset>

    <? if (count($neededConfigs)) : ?>
        <fieldset>
            <legend>
                <?= _("Konfigurationen") ?>
            </legend>

            <?php foreach ($neededConfigs as $config_name) : ?>
                <label>
                    <?= htmlReady($config_name) ?>
                    <textarea name="configs[<?= htmlReady($config_name) ?>]"><?= htmlReady($process->getConfig($config_name)) ?></textarea>
                </label>
            <? endforeach ?>
        </fieldset>
    <? endif ?>

    <? if (count($process->tables) > 0) : ?>
        <fieldset>
            <legend>
                <?= _("Tabellen aktivieren") ?>
            </legend>

            <?php foreach ($process->tables as $table) : ?>
                <input type="hidden" value="0" name="activetable[<?= htmlReady($table->getId()) ?>]">
                <label>
                    <input type="checkbox" value="1" name="activetable[<?= htmlReady($table->getId()) ?>]"<?= $table['active'] ? " checked" : "" ?>>
                    <?= htmlReady($table['name']) ?>
                </label>
            <? endforeach ?>
        </fieldset>
    <? endif ?>

    <div data-dialog-button>
        <?= \Studip\Button::create(_("Speichern")) ?>
        <? if (!$process->isNew()) : ?>
            <?= \Studip\Button::create(_("Löschen"), "delete_process", array('onclick' => "return window.confirm('"._("Wirklich löschen?")."');")) ?>
        <? endif ?>
    </div>
</form>
