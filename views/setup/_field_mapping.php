<? $dynamically_mapped = in_array($field, $table->fieldsToBeDynamicallyMapped()) ?>
<tr style="<?= $dynamically_mapped ? "opacity: 0.5;" : "" ?>" class="<?= $dynamically_mapped ? "dynamically_mapped" : "" ?>">
    <td>
        <? if (!$dynamically_mapped) : ?>
        <label for="simplematching_<?= htmlReady($field) ?>">
            <? endif ?>
            <?= htmlReady($field) ?>
            <? if (!$dynamically_mapped) : ?>
        </label>
    <? endif ?>
    </td>
    <td>
        <? if ($dynamically_mapped) : ?>
            <?= _("Wird vom Plugin dynamisch gemapped") ?>
        <? else : ?>
            <select name="tabledata[simplematching][<?= htmlReady($field) ?>][column]"
                    id="simplematching_<?= htmlReady($field) ?>"
                    onChange="jQuery('#simplematching_<?= htmlReady($field) ?>_static').toggle(this.value === 'static value'); jQuery('#simplematching_<?= htmlReady($field) ?>_mapfrom').toggle(this.value.indexOf('fleximport_mapper__') === 0); jQuery('#simplematching_<?= htmlReady($field) ?>_format').toggle(this.value !== '');"
                    title="<?= htmlReady($placeholder) ?>">
                <option value="" title="<?= _("Wert wird nicht gemapped") ?>"></option>
                <option value="static value"<?= $table['tabledata']['simplematching'][$field]['column'] === "static value" ? " selected" : "" ?>>[<?= _("Fester Eintrag") ?>]</option>

                <optgroup label="<?= _("Feldwerte") ?>">
                    <? foreach ($table->getTableHeader() as $header) : ?>
                        <? if ($header !== "IMPORT_TABLE_PRIMARY_KEY") : ?>
                            <option value="<?= htmlReady($header) ?>"<?= $header === $table['tabledata']['simplematching'][$field]['column'] ? " selected" : "" ?>>
                                <?= htmlReady($header) ?>
                            </option>
                        <? endif ?>
                    <? endforeach ?>
                </optgroup>

                <? if (count($mapperclasses)) : ?>
                    <? $mapper_exists = false ?>
                    <? foreach ($mapperclasses as $class) {
                        $mapper = new $class();
                        if (in_array(strtolower($field), $mapper->possibleFieldnames())) {
                            $mapper_exists = true;
                        }
                    } ?>
                    <? if ($mapper_exists) : ?>
                    <optgroup label="<?= _("Spezialmapper") ?>">
                    <? foreach ($mapperclasses as $class) {
                        $mapper = new $class();
                        if (in_array(strtolower($field), $mapper->possibleFieldnames())) {
                            foreach ($mapper->possibleFormats() as $index => $value) : ?>
                                <? $optionvalue = "fleximport_mapper__".$class."__".$index ?>
                                <option value="<?= htmlReady($optionvalue) ?>"<?= $optionvalue === $table['tabledata']['simplematching'][$field]['column'] ? " selected" : "" ?>>
                                    <?= sprintf(_("Von %s ermitteln"), $value) ?>
                                </option>
                            <? endforeach;
                        }
                    } ?>
                    </optgroup>
                    <? endif ?>
                <? endif ?>

                <? $configs = FleximportConfig::all() ?>
                <? if (count($configs)) : ?>
                    <optgroup label="<?= _("Templates") ?>">
                    <? foreach ($configs as $configname => $value) : ?>
                        <option value="fleximportconfig_<?= htmlReady($configname) ?>"<?= "fleximportconfig_".$configname === $table['tabledata']['simplematching'][$field]['column'] ? " selected" : ""?>>
                            <?= _("Konfiguration: ").htmlReady($configname) ?>
                        </option>
                    <? endforeach ?>
                    </optgroup>
                <? endif ?>

                <? if (in_array($table['import_type'], (array) array("Course", "CourseMember"))) : ?>
                    <? if ($field === "seminar_id") : ?>
                        <optgroup label="<?= _("Weitere") ?>">
                            <option value="fleximport_map_from_veranstaltungsnummer_and_semester"<?= $table['tabledata']['simplematching']['seminar_id']['column'] === "fleximport_map_from_veranstaltungsnummer_and_semester" ? " selected" : "" ?>>
                                <?= _("Von Veranstaltungsnummer und Semester ermitteln") ?>
                            </option>
                        </optgroup>
                    <? endif ?>
                <? endif ?>
            </select>
            <div id="simplematching_<?= htmlReady($field) ?>_static" style="<?= $table['tabledata']['simplematching'][$field]['column'] !== "static value" ? "display: none;" : "" ?>">
                <input type="text"
                       name="tabledata[simplematching][<?= htmlReady($field) ?>][static]"
                       value="<?= htmlReady($table['tabledata']['simplematching'][$field]['static']) ?>"
                       placeholder="<?= htmlReady($placeholder) ?>">
            </div>

            <? if (count($mapperclasses)) : ?>
                <select id="simplematching_<?= htmlReady($field) ?>_mapfrom"
                        name="tabledata[simplematching][<?= htmlReady($field) ?>][mapfrom]"
                        style="<?= strpos($table['tabledata']['simplematching'][$field]['column'], "fleximport_mapper__") === 0 ? "" : "display: none;" ?>">
                    <option value=""><?= _("Aus Spalte ...") ?></option>
                    <? foreach ($table->getTableHeader() as $header) : ?>
                        <? if ($header !== "IMPORT_TABLE_PRIMARY_KEY") : ?>
                            <option value="<?= htmlReady($header) ?>"<?= $header === $table['tabledata']['simplematching'][$field]['mapfrom'] ? " selected" : "" ?>>
                                <?= htmlReady($header) ?>
                            </option>
                        <? endif ?>
                    <? endforeach ?>
                </select>
            <? endif ?>

            <? if ($delimiter) : ?>
                <label>
                    <?= _("Trennzeichen") ?>
                    <input type="text"
                           name="tabledata[simplematching][<?= htmlReady($field) ?>][delimiter]"
                           value="<?= htmlReady($table['tabledata']['simplematching'][$field]['delimiter']) ?>"
                           placeholder="<?= _("; , | ") ?>"
                           style="width: 50px; display: inline; vertical-align: baseline">
                </label>
            <? endif ?>

            <? if (($table['import_type'] === "Course") && ($field === "status")) : ?>
                <div class="format" id="simplematching_<?= htmlReady($field) ?>_format">
                    <select name="tabledata[simplematching][<?= htmlReady($field) ?>][format]">
                        <option value=""><?= _("Format: sem_type_id") ?></option>
                        <option value="name"<?= $table['tabledata']['simplematching'][$field]['format'] === "name" ? " selected" : "" ?>><?= _("Format: Veranstaltungstyp-Name") ?></option>
                    </select>
                </div>
            <? endif ?>
            <? if (($table['import_type'] === "User") && ($field === "username")) : ?>
                <div class="format" id="simplematching_<?= htmlReady($field) ?>_format">
                    <select name="tabledata[simplematching][<?= htmlReady($field) ?>][format]">
                        <option value="cleartext"><?= _("Format: Reiner Text") ?></option>
                        <option value="email_first_part"<?= $table['tabledata']['simplematching'][$field]['format'] === "email_first_part" ? " selected" : "" ?>><?= _("Format: Erster Teil der Email") ?></option>
                    </select>
                </div>
            <? endif ?>
        <? endif ?>
    </td>