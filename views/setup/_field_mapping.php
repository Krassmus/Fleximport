<? $dynamically_mapped = in_array($field, $table->fieldsToBeDynamicallyMapped()) ?>
<tr style="<?= $dynamically_mapped ? "opacity: 0.5;" : "" ?>" class="<?= $dynamically_mapped ? "dynamically_mapped" : "" ?>">
    <td>
        <? if (!$dynamically_mapped) : ?>
        <label for="simplematching_<?= md5($field) ?>">
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
                    id="simplematching_<?= md5($field) ?>"
                    onChange="STUDIP.Fleximport.changeMappingOfField.call(this, '<?= md5($field) ?>');"
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
                    <optgroup label="<?= _("Spezialmapper") ?>">
                    <? foreach ($mapperclasses as $class) {
                        $mapper = new $class();
                        if (in_array("*", $mapper->possibleFieldnames()) || in_array(strtolower($field), $mapper->possibleFieldnames())) {
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

                <? if (count($configs)) : ?>
                    <optgroup label="<?= _("Key-Value-Mapper") ?>">
                        <? foreach ($configs as $configname => $value) : ?>
                            <option value="fleximportkeyvalue_<?= htmlReady($configname) ?>"<?= "fleximportkeyvalue_".$configname === $table['tabledata']['simplematching'][$field]['column'] ? " selected" : ""?>>
                                <?= _("Key-Value: ").htmlReady($configname) ?>
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
            <div id="simplematching_<?= md5($field) ?>_static" style="<?= $table['tabledata']['simplematching'][$field]['column'] !== "static value" ? "display: none;" : "" ?>">
                <input type="text"
                       name="tabledata[simplematching][<?= htmlReady($field) ?>][static]"
                       value="<?= htmlReady($table['tabledata']['simplematching'][$field]['static']) ?>"
                       placeholder="<?= htmlReady($placeholder) ?>">
            </div>

            <? if (count($mapperclasses)) : ?>
                <select id="simplematching_<?= md5($field) ?>_mapfrom"
                        name="tabledata[simplematching][<?= htmlReady($field) ?>][mapfrom]"
                        style="<?= (strpos($table['tabledata']['simplematching'][$field]['column'], "fleximport_mapper__") === 0) || (strpos($table['tabledata']['simplematching'][$field]['column'], "fleximportkeyvalue_") === 0) ? "" : "display: none;" ?>">
                    <option value=""><?= _("Aus Spalte ...") ?></option>
                    <optgroup label="<?= _("Feldwerte") ?>">
                        <? foreach ($table->getTableHeader() as $header) : ?>
                            <? if ($header !== "IMPORT_TABLE_PRIMARY_KEY") : ?>
                                <option value="<?= htmlReady($header) ?>"<?= $header === $table['tabledata']['simplematching'][$field]['mapfrom'] ? " selected" : "" ?>>
                                    <?= htmlReady($header) ?>
                                </option>
                            <? endif ?>
                        <? endforeach ?>
                    </optgroup>
                    <? if (count($configs)) : ?>
                        <optgroup label="<?= _("Templates") ?>">
                            <? foreach ($configs as $configname => $value) : ?>
                                <option value="fleximportconfig_<?= htmlReady($configname) ?>"<?= "fleximportconfig_".$configname === $table['tabledata']['simplematching'][$field]['mapfrom'] ? " selected" : ""?>>
                                    <?= _("Konfiguration: ").htmlReady($configname) ?>
                                </option>
                            <? endforeach ?>
                        </optgroup>
                    <? endif ?>
                </select>
            <? endif ?>

            <div class="fleximport_foreign_key_sormclass"
                 id="simplematching_<?= md5($field) ?>_foreignkey_sormclass"
                 style="<?= $table['tabledata']['simplematching'][$field]['column'] === "fleximport_mapper__FleximportForeignKeyMapper__fleximport_foreign_key" ? '' : 'display: none;'?>">
                <label>
                    <?= _("Name der SORM-Klasse des Schlüssels") ?>
                    <input type="text"
                           name="tabledata[simplematching][<?= htmlReady($field) ?>][fleximport_foreign_key_sormclass]"
                           placeholder="<?= htmlReady($table['import_type']) ?>"
                           value="<?= htmlReady($table['tabledata']['simplematching'][$field]['fleximport_foreign_key_sormclass']) ?>">
                </label>
            </div>

            <? if ($delimiter) : ?>
                <div id="simplematching_<?= md5($field) ?>_delimiter" style="<?= $table['tabledata']['simplematching'][$field]['column'] ? "display: flex;" : ' display: none;' ?>">
                    <div style="width: 33%;">
                        <label style="display: inline;">
                            <?= _("Trennzeichen") ?>
                            <input type="text"
                                   name="tabledata[simplematching][<?= htmlReady($field) ?>][delimiter]"
                                   value="<?= htmlReady($table['tabledata']['simplematching'][$field]['delimiter'] ?: ";") ?>"
                                   placeholder="<?= _("Bspw. ; , |") ?>"
                                   style="width: 65px; display: inline; vertical-align: baseline">
                        </label>
                        <? $title = _("Dieses Feld kann mehrere Einträge haben. Sie können diese Einträge mit einem Zeichen trennen. Zum Beispiel ein Semikolon oder ein | Zeichen oder ein ganzes Wort. Auch reguläre Ausdrücke sind hier möglich.") ?>
                        <?= Icon::create("info-circle", "inactive")->asImg(20, array('class' => "text-bottom", 'title' => $title, 'onclick' => "alert('".addslashes($title)."');", 'style' => "cursor: pointer")) ?>
                    </div>
                    <div style="width: 33%;">
                        <label>
                            <input type="checkbox"
                                   name="tabledata[simplematching][<?= htmlReady($field) ?>][sync]"
                                   <?= $table['tabledata']['simplematching'][$field]['sync'] ? "checked" : "" ?>
                                   value="1">
                            <?= _("Synchronisieren") ?>
                        </label>
                    </div>
                    <div style="width: 33%; padding-top: 7px;">
                        <select name="tabledata[simplematching][<?= htmlReady($field) ?>][dynamic_template]">
                            <option value=""><?= _("Mit Template ...") ?></option>
                            <? foreach ($configs as $configname => $value) : ?>
                                <option value="<?= htmlReady($configname) ?>"<?= $configname === $table['tabledata']['simplematching'][$field]['dynamic_template'] ? " selected" : ""?>>
                                    <?= htmlReady($configname) ?>
                                </option>
                            <? endforeach ?>
                        </select>
                    </div>
                </div>
            <? endif ?>

            <? if (($table['import_type'] === "Course") && ($field === "status")) : ?>
                <div class="format" id="simplematching_<?= md5($field) ?>_format">
                    <select name="tabledata[simplematching][<?= htmlReady($field) ?>][format]">
                        <option value=""><?= _("Format: sem_type_id") ?></option>
                        <option value="name"<?= $table['tabledata']['simplematching'][$field]['format'] === "name" ? " selected" : "" ?>><?= _("Format: Veranstaltungstyp-Name") ?></option>
                    </select>
                </div>
            <? endif ?>

            <? if (($table['import_type'] === "User") && ($field === "password")) : ?>
                <div class="format" id="simplematching_<?= md5($field) ?>_format">
                    <input type="hidden"
                           name="tabledata[simplematching][<?= htmlReady($field) ?>][hash]"
                           value="0">
                    <label>
                        <input type="checkbox"
                               name="tabledata[simplematching][<?= htmlReady($field) ?>][hash]"
                               value="1"<?= $table['tabledata']['simplematching'][$field]['hash'] ? " checked" : "" ?>>
                        <?= _("Passwort mit BCrypt hashen") ?>
                    </label>
                </div>
            <? endif ?>

        <? endif ?>
    </td>
    <td>
        <input type="checkbox" name="tabledata[ignoreonupdate][]" title="<?= _("Ignorieren bei Update der Daten?") ?>"
               value="<?= htmlReady($field) ?>"<?= in_array($field, (array) $table['tabledata']['ignoreonupdate']) ? " checked" : "" ?>>
    </td>
