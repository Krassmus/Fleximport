<form action="<?= PluginEngine::getLink($plugin, array(), "setup/tablemapping/".$table->getId()) ?>"
      method="post"
      class="studip_form"
      data-dialog>

    <? if (false && $table['import_type'] === "Course") : ?>
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
                    <td></td>
                </tr>
            </tbody>
        </table>
    <? endif ?>

    <table class="default nohover">
        <caption>
            <?= _("Einfache Mappings") ?>
        </caption>
        <thead>
            <tr>
                <th><?= _("Spalte der Zieltabelle") ?></th>
                <th><?= _("Spalte der Datentabelle") ?></th>
            </tr>
        </thead>
        <tbody>
        <? foreach ($table->getTargetFields() as $fieldname) : ?>
            <? $dynamically_mapped = in_array($fieldname, $table->fieldsToBeDynamicallyMapped()) ?>
            <tr style="<?= $dynamically_mapped ? "opacity: 0.5;" : "" ?>" class="<?= $dynamically_mapped ? "dynamically_mapped" : "" ?>">
                <td>
                    <? if ($dynamically_mapped) : ?>
                    <label for="simplematching_<?= htmlReady($fieldname) ?>">
                    <? endif ?>
                        <?= htmlReady($fieldname) ?>
                    <? if ($dynamically_mapped) : ?>
                    </label>
                    <? endif ?>
                </td>
                <td>
                    <? if ($dynamically_mapped) : ?>
                        <?= _("Wird von einem Plugin dynamisch gemapped") ?>
                    <? else : ?>
                    <select name="tabledata[simplematching][<?= htmlReady($fieldname) ?>][column]"
                            id="simplematching_<?= htmlReady($fieldname) ?>"
                            onClick="jQuery('#simplematching_<?= htmlReady($fieldname) ?>_static').toggle(this.value === 'static value');">
                        <option value="" title="<?= _("Wert wird nicht gemapped") ?>"></option>
                        <option value="static value"<?= $table['tabledata']['simplematching'][$fieldname]['column'] === "static value" ? " selected" : "" ?>>[<?= _("Fester Eintrag") ?>]</option>
                        <? foreach ($table->getTableHeader() as $header) : ?>
                            <? if ($header !== "IMPORT_TABLE_PRIMARY_KEY") : ?>
                            <option value="<?= htmlReady($header) ?>"<?= $header === $table['tabledata']['simplematching'][$fieldname]['column'] ? " selected" : "" ?>>
                                <?= htmlReady($header) ?>
                            </option>
                            <? endif ?>
                        <? endforeach ?>
                    </select>
                    <div id="simplematching_<?= htmlReady($fieldname) ?>_static" style="<?= $table['tabledata']['simplematching'][$fieldname]['column'] !== "static value" ? "display: none;" : "" ?>">
                        <input type="text"
                               name="tabledata[simplematching][<?= htmlReady($fieldname) ?>][static]"
                               value="<?= htmlReady($table['tabledata']['simplematching'][$fieldname]['static']) ?>">
                    </div>
                    <? endif ?>
                </td>
            </tr>
        <? endforeach ?>
        </tbody>
    </table>

    <div style="text-align: center;" data-dialog-button>
        <?= \Studip\Button::create(_("Speichern")) ?>
    </div>

</form>