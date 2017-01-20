<form action="<?= PluginEngine::getLink($plugin, array(), "setup/tablemapping/".$table->getId()) ?>"
      method="post"
      class="<?= Fleximport::getCSSFormClass() ?>"
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
            <?= $this->render_partial("setup/_field_mapping.php", array(
                'field' => $fieldname,
                'table' => $table
            )) ?>
        <? endforeach ?>

        <? foreach ($datafields as $datafield) : ?>
            <?= $this->render_partial("setup/_field_mapping.php", array(
                'field' => $datafield['name'],
                'table' => $table
            )) ?>
        <? endforeach ?>


        <? if ($table['import_type'] === "Course") : ?>
            <?= $this->render_partial("setup/_field_mapping.php", array(
                'field' => "fleximport_dozenten",
                'table' => $table,
                'placeholder' => _("kommaseparierte user_ids")
            )) ?>
            <?= $this->render_partial("setup/_field_mapping.php", array(
                'field' => "fleximport_studyarea",
                'table' => $table,
                'placeholder' => _("semikolonseparierte sem_tree_ids")
            )) ?>
            <?= $this->render_partial("setup/_field_mapping.php", array(
                'field' => "fleximport_locked",
                'table' => $table,
                'placeholder' => _("1 für gesperrt")
            )) ?>
            <?= $this->render_partial("setup/_field_mapping.php", array(
                'field' => "fleximport_course_userdomains",
                'table' => $table,
                'placeholder' => _("kommaseparierte Domänennamen oder Domänen-IDs")
            )) ?>
        <? endif ?>
        <? if ($table['import_type'] === "User") : ?>
            <?= $this->render_partial("setup/_field_mapping.php", array(
                'field' => "fleximport_username_prefix",
                'table' => $table,
                'placeholder' => _("Präfix für den Nutzernamen")
            )) ?>
            <?= $this->render_partial("setup/_field_mapping.php", array(
                'field' => "fleximport_user_inst",
                'table' => $table,
                'placeholder' => _("kommaseparierte Einrichtungsnamen")
            )) ?>
            <?= $this->render_partial("setup/_field_mapping.php", array(
                'field' => "fleximport_userdomains",
                'table' => $table,
                'placeholder' => _("kommaseparierte Domänennamen oder Domänen-IDs")
            )) ?>
            <?= $this->render_partial("setup/_field_mapping.php", array(
                'field' => "fleximport_expiration_date",
                'table' => $table,
                'placeholder' => _("Datum")
            )) ?>

            <? $dynamically_mapped = in_array("fleximport_welcome_message", $table->fieldsToBeDynamicallyMapped()) ?>
            <tr style="<?= $dynamically_mapped ? "opacity: 0.5;" : "" ?>" class="<?= $dynamically_mapped ? "dynamically_mapped" : "" ?>">
                <td>
                    fleximport_welcome_message
                    <div style="font-size: 0.8em;"><?= _("Nachricht, die an neue Nutzer versendet wird. Bei Fleximport-Variablen schreiben Sie {{password}} oder {{link}} oder andere Parameter in die Nachricht.") ?></div>
                </td>
                <td>
                    <? if ($dynamically_mapped) : ?>
                        <?= _("Wird von einem Plugin dynamisch gemapped") ?>
                    <? else : ?>
                        <select name="tabledata[simplematching][fleximport_welcome_message][column]">
                            <option value=""><?= _("Standardnachricht mit Passwort an Nutzer") ?></option>
                            <option value="none"<?= "none" === $table['tabledata']['simplematching']['fleximport_welcome_message']['column'] ? " selected" : "" ?>><?= _("Keine Nachricht versenden") ?></option>
                            <? foreach (FleximportConfig::all() as $config => $value) : ?>
                                <option value="<?= htmlReady($config) ?>"<?= $config === $table['tabledata']['simplematching']['fleximport_welcome_message']['column'] ? " selected" : "" ?>><?= htmlReady($config) ?></option>
                            <? endforeach ?>
                        </select>
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