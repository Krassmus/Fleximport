<form action="<?= PluginEngine::getLink($plugin, array(), "setup/tablemapping/".$table->getId()) ?>" method="post" class="studip_form">
    <table class="default nohover">
        <caption><?= _("Dozent") ?></caption>
        <tbody>
            <tr>
                <td>
                    <label>
                        <input type="radio">
                        <?= _("Dummy-Dozent") ?>
                    </label>
                </td>
                <td>
                    <?= QuickSearch::get("dozent_id", new StandardSearch("user_id"))->render() ?>
                </td>
            </tr>
            <tr>
                <td>
                    <label>
                        <input type="radio">
                        <?= _("Spalte") ?>
                    </label>
                </td>
                <td>
                    <select>
                        <option>Spalte 1</option>
                        <option>Spalte 2</option>
                        <option>Spalte 3</option>
                    </select>
                    <select>
                        <option>username</option>
                        <option>email</option>
                        <option>Datenfeld 1</option>
                        <option>Datenfeld 2</option>
                    </select>
                    <label>
                        <input type="checkbox">
                        <?= _("Semikolongetrennt") ?>
                    </label>
                </td>
            </tr>
            <tr>
                <td>
                    <label>
                        <input type="radio">
                        <?= _("Spalte") ?>
                    </label>
                </td>
            </tr>
        </tbody>
    </table>
</form>