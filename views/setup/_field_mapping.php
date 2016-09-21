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
                    onClick="jQuery('#simplematching_<?= htmlReady($field) ?>_static').toggle(this.value === 'static value');">
                <option value="" title="<?= _("Wert wird nicht gemapped") ?>"></option>
                <option value="static value"<?= $table['tabledata']['simplematching'][$field]['column'] === "static value" ? " selected" : "" ?>>[<?= _("Fester Eintrag") ?>]</option>
                <? foreach ($table->getTableHeader() as $header) : ?>
                    <? if ($header !== "IMPORT_TABLE_PRIMARY_KEY") : ?>
                        <option value="<?= htmlReady($header) ?>"<?= $header === $table['tabledata']['simplematching'][$field]['column'] ? " selected" : "" ?>>
                            <?= htmlReady($header) ?>
                        </option>
                    <? endif ?>
                <? endforeach ?>

                <? foreach (get_declared_classes() as $class) {
                    if (is_subclass_of($class, "FleximportMapper") && $class !== "FleximportMapper") {
                        $mapper = new $class();
                        if (in_array($field, $mapper->possibleFieldnames())) {
                            foreach ($mapper->possibleFormats() as $index => $value) : ?>
                                <? $optionvalue = "fleximport_mapper__".$class."__".$index ?>
                                <option value="<?= htmlReady($optionvalue) ?>"<?= $optionvalue === $table['tabledata']['simplematching'][$field]['column'] ? " selected" : "" ?>>
                                    <?= sprintf(_("Von %s ermitteln"), $value) ?>
                                </option>
                            <? endforeach;
                        }
                    }
                } ?>


                <? if (in_array($table['import_type'], (array) array("Course", "CourseMember"))) : ?>
                    <? if ($field === "seminar_id") : ?>
                        <option value="fleximport_map_from_veranstaltungsnummer"<?= $table['tabledata']['simplematching']['seminar_id']['column'] === "fleximport_map_from_veranstaltungsnummer" ? " selected" : "" ?>>
                            <?= _("Von Veranstaltungsnummer ermitteln") ?>
                        </option>
                        <option value="fleximport_map_from_name"<?= $table['tabledata']['simplematching']['seminar_id']['column'] === "fleximport_map_from_name" ? " selected" : "" ?>>
                            <?= _("Von Veranstaltungsname ermitteln") ?>
                        </option>
                        <option value="fleximport_map_from_veranstaltungsnummer_and_semester"<?= $table['tabledata']['simplematching']['seminar_id']['column'] === "fleximport_map_from_veranstaltungsnummer_and_semester" ? " selected" : "" ?>>
                            <?= _("Von Veranstaltungsnummer und Semester ermitteln") ?>
                        </option>
                    <? endif ?>
                    <? if ($field === "start_time") : ?>
                        <option value="fleximport_current_semester"<?= $table['tabledata']['simplematching']['start_time']['column'] === "fleximport_current_semester" ? " selected" : "" ?>>
                            <?= _("Aktuelles Semester") ?>
                        </option>
                        <option value="fleximport_next_semester"<?= $table['tabledata']['simplematching']['start_time']['column'] === "fleximport_next_semester" ? " selected" : "" ?>>
                            <?= _("Kommendes Semester") ?>
                        </option>
                    <? endif ?>
                <? endif ?>
                <? if ($table['import_type'] === "User") : ?>
                    <? if ($field === "user_id") : ?>
                        <option value="fleximport_map_from_username"<?= $table['tabledata']['simplematching']['user_id']['column'] === "fleximport_map_from_username" ? " selected" : "" ?>>
                            <?= _("Von Username ermitteln") ?>
                        </option>
                        <option value="fleximport_map_from_email"<?= $table['tabledata']['simplematching']['user_id']['column'] === "fleximport_map_from_email" ? " selected" : "" ?>>
                            <?= _("Von Email ermitteln") ?>
                        </option>
                        <? foreach (Datafield::findBySQL("object_type = 'user' ORDER BY name") as $datafield) : ?>
                            <option value="fleximport_map_from_datafield_<?= $datafield->getId() ?>"<?= $table['tabledata']['simplematching']['user_id']['column'] === "fleximport_map_from_datafield_".$datafield->getId() ? " selected" : "" ?>>
                                <?= sprintf(_("Von Datenfeld '%s' ermitteln"), $datafield['name']) ?>
                            </option>
                        <? endforeach ?>
                    <? endif ?>
                <? endif ?>
            </select>
            <div id="simplematching_<?= htmlReady($field) ?>_static" style="<?= $table['tabledata']['simplematching'][$field]['column'] !== "static value" ? "display: none;" : "" ?>">
                <input type="text"
                       name="tabledata[simplematching][<?= htmlReady($field) ?>][static]"
                       value="<?= htmlReady($table['tabledata']['simplematching'][$field]['static']) ?>"
                       placeholder="<?= htmlReady($placeholder) ?>">
            </div>

            <? if (($table['import_type'] === "Course") && ($field === "fleximport_dozenten")) : ?>
                <div id="simplematching_<?= htmlReady($field) ?>_format" style="<?= !$table['tabledata']['simplematching']['fleximport_dozenten']['column'] || $table['tabledata']['simplematching'][$field]['column'] === "static value" ? "display: none;" : "" ?>">
                    <select name="tabledata[simplematching][<?= htmlReady($field) ?>][format]">
                        <option value="user_id"<?= $table['tabledata']['simplematching'][$field]['format'] === "user_id" ? " selected" : "" ?>><?= _("Format: user_ids (mit Leerzeichen getrennt)") ?></option>
                        <option value="username"<?= $table['tabledata']['simplematching'][$field]['format'] === "username" ? " selected" : "" ?>><?= _("Format: Nutzernamen (mit Leerzeichen getrennt)") ?></option>
                        <option value="email"<?= $table['tabledata']['simplematching'][$field]['format'] === "email" ? " selected" : "" ?>><?= _("Format: Emails (mit Leerzeichen getrennt)") ?></option>
                        <option value="fullname"<?= $table['tabledata']['simplematching'][$field]['format'] === "fullname" ? " selected" : "" ?>><?= _("Format: Vorname Nachname (mit Kommata getrennt)") ?></option>
                        <? foreach (Datafield::findBySQL("object_type = 'user'") as $datafield) : ?>
                            <option value="<?= htmlReady($datafield->getId()) ?>"<?= $table['tabledata']['simplematching'][$field]['format'] === $datafield->getId() ? " selected" : "" ?>><?= htmlReady(sprintf(_("Format: %s (mit Leerzeichen getrennt)"), $datafield['name'])) ?></option>
                        <? endforeach ?>
                    </select>
                </div>
            <? endif ?>
            <? if (($table['import_type'] === "Course") && ($field === "institut_id")) : ?>
                <div class="format" id="simplematching_<?= htmlReady($field) ?>_format">
                    <select name="tabledata[simplematching][<?= htmlReady($field) ?>][format]">
                        <option value=""><?= _("Format: Institut_id") ?></option>
                        <option value="name"<?= $table['tabledata']['simplematching'][$field]['format'] === "name" ? " selected" : "" ?>><?= _("Format: Name") ?></option>
                        <? foreach (Datafield::findBySQL("object_type = 'inst' ORDER BY name") as $datafield) : ?>
                            <option value="<?= $datafield->getId() ?>"<?= $table['tabledata']['simplematching'][$field]['format'] === $datafield->getId() ? " selected" : "" ?>>
                                <?= htmlReady(_("Format: ").$datafield['name']) ?>
                            </option>
                        <? endforeach ?>
                    </select>
                </div>
            <? endif ?>
            <? if (($table['import_type'] === "Course") && ($field === "start_time")) : ?>
                <div class="format" id="simplematching_<?= htmlReady($field) ?>_format">
                    <select name="tabledata[simplematching][<?= htmlReady($field) ?>][format]">
                        <option value=""><?= _("Format: Unix-Timestamp") ?></option>
                        <option value="name"<?= $table['tabledata']['simplematching'][$field]['format'] === "name" ? " selected" : "" ?>><?= _("Format: Name") ?></option>
                    </select>
                </div>
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