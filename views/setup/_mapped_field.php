<? $dynamically_mapped = in_array($fieldname, $table->fieldsToBeDynamicallyMapped()) ?>
<tr style="<?= $dynamically_mapped ? "opacity: 0.5;" : "" ?>" class="<?= $dynamically_mapped ? "dynamically_mapped" : "" ?>">
    <td>
        <? if (!$dynamically_mapped) : ?>
        <label for="simplematching_<?= htmlReady($fieldname) ?>">
            <? endif ?>
            <?= htmlReady($fieldname) ?>
            <? if (!$dynamically_mapped) : ?>
        </label>
    <? endif ?>
    </td>
    <td>
        <? if ($dynamically_mapped) : ?>
            <?= _("Wird vom Plugin dynamisch gemapped") ?>
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
                <? if (in_array($table['import_type'], (array) array("Course", "CourseMember"))) : ?>
                    <? if ($fieldname === "seminar_id") : ?>
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
                    <? if ($fieldname === "start_time") : ?>
                        <option value="fleximport_current_semester"<?= $table['tabledata']['simplematching']['start_time']['column'] === "fleximport_current_semester" ? " selected" : "" ?>>
                            <?= _("Aktuelles Semester") ?>
                        </option>
                        <option value="fleximport_next_semester"<?= $table['tabledata']['simplematching']['start_time']['column'] === "fleximport_next_semester" ? " selected" : "" ?>>
                            <?= _("Kommendes Semester") ?>
                        </option>
                    <? endif ?>
                <? endif ?>
                <? if ($table['import_type'] === "User") : ?>
                    <? if ($fieldname === "user_id") : ?>
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
            <div id="simplematching_<?= htmlReady($fieldname) ?>_static" style="<?= $table['tabledata']['simplematching'][$fieldname]['column'] !== "static value" ? "display: none;" : "" ?>">
                <input type="text"
                       name="tabledata[simplematching][<?= htmlReady($fieldname) ?>][static]"
                       value="<?= htmlReady($table['tabledata']['simplematching'][$fieldname]['static']) ?>">
            </div>

            <? if (($table['import_type'] === "Course") && ($fieldname === "institut_id")) : ?>
                <div class="format" id="simplematching_<?= htmlReady($fieldname) ?>_format">
                    <select name="tabledata[simplematching][<?= htmlReady($fieldname) ?>][format]">
                        <option value=""><?= _("Format: Institut_id") ?></option>
                        <option value="name"<?= $table['tabledata']['simplematching'][$fieldname]['format'] === "name" ? " selected" : "" ?>><?= _("Format: Name") ?></option>
                        <? foreach (Datafield::findBySQL("object_type = 'inst' ORDER BY name") as $datafield) : ?>
                            <option value="<?= $datafield->getId() ?>"<?= $table['tabledata']['simplematching'][$fieldname]['format'] === $datafield->getId() ? " selected" : "" ?>>
                                <?= htmlReady(_("Format: ").$datafield['name']) ?>
                            </option>
                        <? endforeach ?>
                    </select>
                </div>
            <? endif ?>
            <? if (($table['import_type'] === "Course") && ($fieldname === "start_time")) : ?>
                <div class="format" id="simplematching_<?= htmlReady($fieldname) ?>_format">
                    <select name="tabledata[simplematching][<?= htmlReady($fieldname) ?>][format]">
                        <option value=""><?= _("Format: Unix-Timestamp") ?></option>
                        <option value="name"<?= $table['tabledata']['simplematching'][$fieldname]['format'] === "name" ? " selected" : "" ?>><?= _("Format: Name") ?></option>
                    </select>
                </div>
            <? endif ?>
            <? if (($table['import_type'] === "Course") && ($fieldname === "status")) : ?>
                <div class="format" id="simplematching_<?= htmlReady($fieldname) ?>_format">
                    <select name="tabledata[simplematching][<?= htmlReady($fieldname) ?>][format]">
                        <option value=""><?= _("Format: sem_type_id") ?></option>
                        <option value="name"<?= $table['tabledata']['simplematching'][$fieldname]['format'] === "name" ? " selected" : "" ?>><?= _("Format: Veranstaltungstyp-Name") ?></option>
                    </select>
                </div>
            <? endif ?>
            <? if (($table['import_type'] === "User") && ($fieldname === "username")) : ?>
                <div class="format" id="simplematching_<?= htmlReady($fieldname) ?>_format">
                    <select name="tabledata[simplematching][<?= htmlReady($fieldname) ?>][format]">
                        <option value="cleartext"><?= _("Format: Reiner Text") ?></option>
                        <option value="email_first_part"<?= $table['tabledata']['simplematching'][$fieldname]['format'] === "email_first_part" ? " selected" : "" ?>><?= _("Format: Erster Teil der Email") ?></option>
                    </select>
                </div>
            <? endif ?>
        <? endif ?>
    </td>
</tr>