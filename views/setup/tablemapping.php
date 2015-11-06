<form action="<?= PluginEngine::getLink($plugin, array(), "setup/tablemapping/".$table->getId()) ?>"
      method="post"
      class="studip_form"
      data-dialog>

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
            <?= $this->render_partial("setup/_mapped_field", array(
                'fieldname' => $fieldname,
                'table' => $table
            )) ?>
        <? endforeach ?>

        <? foreach ($datafields as $datafield) : ?>
            <?= $this->render_partial("setup/_mapped_field", array(
                'fieldname' => $datafield['name'],
                'table' => $table
            )) ?>
        <? endforeach ?>

        <? if ($table['import_type'] === "Course") : ?>
            <? $dynamically_mapped = in_array("fleximport_dozenten", $table->fieldsToBeDynamicallyMapped()) ?>
            <tr style="<?= $dynamically_mapped ? "opacity: 0.5;" : "" ?>" class="<?= $dynamically_mapped ? "dynamically_mapped" : "" ?>">
                <td>fleximport_dozenten</td>
                <td>
                    <? if ($dynamically_mapped) : ?>
                        <?= _("Wird von einem Plugin dynamisch gemapped") ?>
                    <? else : ?>
                    <select name="<?= _("tabledata[simplematching][fleximport_dozenten][column]") ?>" onChange="jQuery('#simplematching_fleximport_dozenten_static').toggle(this.value === 'static value'); jQuery('#simplematching_fleximport_dozenten_format').toggle(this.value && (this.value !== 'static value')); ">
                        <option value="" title="<?= _("Wert wird nicht gemapped") ?>"></option>
                        <option value="static value"<?= $table['tabledata']['simplematching']['fleximport_dozenten']['column'] === "static value" ? " selected" : "" ?>>[<?= _("Fester Eintrag") ?>]</option>
                        <? foreach ($table->getTableHeader() as $header) : ?>
                            <? if ($header !== "IMPORT_TABLE_PRIMARY_KEY") : ?>
                                <option value="<?= htmlReady($header) ?>"<?= $header === $table['tabledata']['simplematching']['fleximport_dozenten']['column'] ? " selected" : "" ?>>
                                    <?= htmlReady($header) ?>
                                </option>
                            <? endif ?>
                        <? endforeach ?>
                    </select>
                    <div id="simplematching_fleximport_dozenten_static" style="<?= $table['tabledata']['simplematching']['fleximport_dozenten']['column'] !== "static value" ? "display: none;" : "" ?>">
                        <input type="text"
                               name="tabledata[simplematching][fleximport_dozenten][static]"
                               value="<?= htmlReady($table['tabledata']['simplematching']['fleximport_dozenten']['static']) ?>"
                               placeholder="<?= _("kommaseparierte user_ids") ?>">
                    </div>
                    <div id="simplematching_fleximport_dozenten_format" style="<?= !$table['tabledata']['simplematching']['fleximport_dozenten']['column'] || $table['tabledata']['simplematching']['fleximport_dozenten']['column'] === "static value" ? "display: none;" : "" ?>">
                        <select name="tabledata[simplematching][fleximport_dozenten][format]">
                            <option value="user_id"<?= $table['tabledata']['simplematching']['fleximport_dozenten']['format'] === "user_id" ? " selected" : "" ?>><?= _("Format: user_ids (mit Leerzeichen getrennt)") ?></option>
                            <option value="username"<?= $table['tabledata']['simplematching']['fleximport_dozenten']['format'] === "username" ? " selected" : "" ?>><?= _("Format: Nutzernamen (mit Leerzeichen getrennt)") ?></option>
                            <option value="email"<?= $table['tabledata']['simplematching']['fleximport_dozenten']['format'] === "email" ? " selected" : "" ?>><?= _("Format: Emails (mit Leerzeichen getrennt)") ?></option>
                            <? foreach (Datafield::findBySQL("object_type = 'user'") as $datafield) : ?>
                                <option value="<?= htmlReady($datafield->getId()) ?>"<?= $table['tabledata']['simplematching']['fleximport_dozenten']['format'] === $datafield->getId() ? " selected" : "" ?>><?= htmlReady(sprintf(_("Format: %s (mit Leerzeichen getrennt)"), $datafield['name'])) ?></option>
                            <? endforeach ?>
                        </select>
                    </div>
                    <? endif ?>
                </td>
            </tr>

            <? $dynamically_mapped = in_array("fleximport_studyarea", $table->fieldsToBeDynamicallyMapped()) ?>
            <tr style="<?= $dynamically_mapped ? "opacity: 0.5;" : "" ?>" class="<?= $dynamically_mapped ? "dynamically_mapped" : "" ?>">
                <td>fleximport_studyarea</td>
                <td>
                    <? if ($dynamically_mapped) : ?>
                        <?= _("Wird von einem Plugin dynamisch gemapped") ?>
                    <? else : ?>
                        <select name="<?= _("tabledata[simplematching][fleximport_studyarea][column]") ?>" onChange="jQuery('#simplematching_fleximport_studyarea_static').toggle(this.value === 'static value'); jQuery('#simplematching_fleximport_studyarea_format').toggle(this.value && (this.value !== 'static value')); ">
                            <option value="" title="<?= _("Wert wird nicht gemapped") ?>"></option>
                            <option value="static value"<?= $table['tabledata']['simplematching']['fleximport_studyarea']['column'] === "static value" ? " selected" : "" ?>>[<?= _("Fester Eintrag") ?>]</option>
                            <? foreach ($table->getTableHeader() as $header) : ?>
                                <? if ($header !== "IMPORT_TABLE_PRIMARY_KEY") : ?>
                                    <option value="<?= htmlReady($header) ?>"<?= $header === $table['tabledata']['simplematching']['fleximport_studyarea']['column'] ? " selected" : "" ?>>
                                        <?= htmlReady($header) ?>
                                    </option>
                                <? endif ?>
                            <? endforeach ?>
                        </select>
                        <div id="simplematching_fleximport_studyarea_static" style="<?= $table['tabledata']['simplematching']['fleximport_dozenten']['column'] !== "static value" ? "display: none;" : "" ?>">
                            <input type="text"
                                   name="tabledata[simplematching][fleximport_studyarea][static]"
                                   value="<?= htmlReady($table['tabledata']['simplematching']['fleximport_studyarea']['static']) ?>"
                                   placeholder="<?= _("semikolonseparierte sem_tree_ids") ?>">
                        </div>
                    <? endif ?>
                </td>
            </tr>
        <? endif ?>
        </tbody>
    </table>

    <h2><?= _("Folgende Felder bei Updates ignorieren") ?></h2>
    <ul>
        <? foreach ($table->getTargetFields() as $fieldname) : ?>
            <li>
                <label>
                    <input type="checkbox" name="tabledata[ignoreonupdate][]"
                           value="<?= htmlReady($fieldname) ?>"<?= in_array($fieldname, (array) $table['tabledata']['ignoreonupdate']) ? " checked" : "" ?>>
                    <?= htmlReady($fieldname) ?>
                </label>
            </li>
        <? endforeach ?>
    </ul>



    <div style="text-align: center;" data-dialog-button>
        <?= \Studip\Button::create(_("Speichern")) ?>
    </div>

</form>