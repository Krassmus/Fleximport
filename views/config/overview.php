<form action="<?= PluginEngine::getLink($plugin, array(), "config/edit") ?>" method="post" class="default">
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
                        <input type="text" name="configs[<?= htmlReady($name) ?>][value]" value="<?= htmlReady($value) ?>" style="width: calc(100% - 20px);">
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
                    <input type="text" name="new_value" value="" style="width: calc(100% - 20px);">
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