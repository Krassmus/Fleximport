<? $already_configs = array() ?>
<form action="<?= PluginEngine::getLink($plugin, array(), "config/edit") ?>"
      method="post"
      class="<?= Fleximport::getCSSFormClass() ?> importconfigs">
    <table class="default">
        <thead>
            <tr>
                <th><?= _("Name") ?></th>
                <th><?= _("Wert") ?></th>
            </tr>
        </thead>
        <tbody>
            <? foreach ($configs as $name => $value) : ?>
                <tr>
                    <td>
                        <input type="text" name="configs[<?= htmlReady($name) ?>][name]" value="<?= htmlReady($name) ?>" style="width: calc(100% - 20px);">
                    </td>
                    <td>
                        <textarea name="configs[<?= htmlReady($name) ?>][value]" style="width: calc(100% - 20px); min-height: 40px; height: 40px;"><?= htmlReady($value) ?></textarea>
                    </td>
                </tr>
                <? $already_configs[] = $name ?>
            <? endforeach ?>
            <? foreach (array_diff($possibleConfigs, $already_configs) as $config_name) : ?>
                <tr>
                    <td>
                        <input type="text" name="configs[<?= htmlReady($config_name) ?>][name]" value="<?= htmlReady($config_name) ?>" style="width: calc(100% - 20px);">
                    </td>
                    <td>
                        <textarea name="configs[<?= htmlReady($config_name) ?>][value]" style="width: calc(100% - 20px); min-height: 40px; height: 40px;"></textarea>
                    </td>
                </tr>
            <? endforeach ?>
        </tbody>
        <tbody>
            <tr>
                <td>
                    <input type="text" name="new_name" value="" style="width: calc(100% - 20px);" placeholder="<?= _("Neuer Parameter") ?>">
                </td>
                <td>
                    <textarea name="new_value" style="width: calc(100% - 20px); min-height: 40px; height: 40px;"></textarea>
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align: center;">
                    <?= \Studip\Button::create(_("Speichern")) ?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>

<script>
    jQuery(function () {
        jQuery(".importconfigs textarea").elastic();
    });
</script>
<?
$actions = new ActionsWidget();
$actions->addLink(
    _("Prozess erstellen"),
    PluginEngine::getURL($plugin, array(), "process/edit"),
    Icon::create("archive2", "clickable"),
    array('data-dialog' => 1)
);
$actions->addLink(
    _("Prozess importieren"),
    PluginEngine::getURL($plugin, array(), "process/import"),
    Icon::create("upload", "clickable"),
    array('data-dialog' => 1)
);


Sidebar::Get()->addWidget($actions);
