<form action="<?= PluginEngine::getLink($plugin, array(), "setup/table".($table->isNew() ? "" : "/".$table->getId())) ?>"
      method="post"
      class="studip_form"
      data-dialog>

    <label>
        <?= _("Tabellenname") ?>
        <input type="text" name="table[name]" value="<?= htmlReady($table['name'] ?: "fleximport_") ?>">
    </label>

    <label>
        <?= _("Zweck der Tabelle") ?>
        <select name="table[import_type]" onChange="jQuery('#other_import_type').toggle(this.value === 'other'); ">
            <option value="User"<?= $table['import_type'] === "User" ? " selected" : "" ?>><?= _("Nutzerimport") ?></option>
            <option value="Course"<?= $table['import_type'] === "Course" ? " selected" : "" ?>><?= _("Veranstaltungsimport") ?></option>
            <option value="CourseMember"<?= $table['import_type'] === "CourseMember" ? " selected" : "" ?>><?= _("Teilnehmerimport") ?></option>
            <option value=""<?= !$table['import_type'] && !$table->isNew() ? " selected" : "" ?>><?= _("Tabelle nicht importieren") ?></option>
            <option value="other"<?= !$table->isNew() && $table['import_type'] && !in_array($table['import_type'], array("User", "CourseMember", "Course")) ? " selected" : "" ?>><?= _("SORM-Objekt") ?></option>
        </select>
        <div id="other_import_type" style="<?= !$table->isNew() && $table['import_type'] && !in_array($table['import_type'], array("User", "CourseMember", "Course")) ? "" : "display: none; " ?>">
            <input type="text" name="other_import_type" value="<?= !$table->isNew() && $table['import_type'] && !in_array($table['import_type'], array("User", "CourseMember", "Course")) ? htmlReady($table['import_type']) : "" ?>" placeholder="<?= _("Name der SORM-Klasse") ?>">
        </div>
    </label>

    <? if ($table->isNew() || !$table->getPlugin() || !$table->getPlugin()->customImportEnabled()) : ?>
        <label>
            <?= _("Import über") ?>
            <select name="table[source]" onChange="jQuery('#server_settings').toggle(this.value == 'database'); jQuery('#weblink_info').toggle(this.value == 'csv_weblink');">
                <option value="csv_upload"<?= $table['source'] === "csv_upload" || $table->isNew() ? " selected" : "" ?>><?= _("CSV-Upload") ?></option>
                <option value="csv_weblink"<?= $table['source'] === "csv_weblink" ? " selected" : "" ?>><?= _("CSV-Internetquelle") ?></option>
                <option value="database"<?= !$table['source'] === "database" ? " selected" : "" ?>><?= _("Datenbank") ?></option>
                <option value="extern"<?= !$table['source'] === "extern" ? " selected" : "" ?>><?= _("Externes Tool") ?></option>
            </select>
        </label>
    <? endif ?>

    <label id="weblink_info" style="<?= $table['source'] !== "csv_weblink" ? "display: none;" : "" ?>">
        <?= _("URL der CSV-Datei") ?>
        <input type="text" name="table[tabledata][weblink][url]" value="<?= htmlReady($table['tabledata']['weblink']['url']) ?>">
    </label>

    <table id="server_settings" class="default nohover" style="<?= $table['source'] !== "database" ? "display: none;" : "" ?>">
        <tbody>
            <tr>
                <td><label for="table_tabledata_server_type"><?= _("Datenbanktyp") ?></label></td>
                <td>
                    <select name="table[tabledata][server][type]" id="table_tabledata_server_type">
                        <option value="mysql"<?= $table['tabledata']['server']['type'] === "mysql" ? " selected" : "" ?>>MySQL</option>
                        <option value="mssql"<?= $table['tabledata']['server']['type'] === "mssql" ? " selected" : "" ?>>MS-SQL Server</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td><label for="table_tabledata_server_adress"><?= _("Server") ?></label></td>
                <td>
                    <input type="text" name="table[tabledata][server][adress]" value="<?= htmlReady($table['tabledata']['server']['adress']) ?>" id="table_tabledata_server_adress" placeholder="127.0.0.1">
                </td>
            </tr>
            <tr>
                <td><label for="table_tabledata_server_port"><?= _("Port") ?></label></td>
                <td>
                    <input type="text" name="table[tabledata][server][port]" value="<?= htmlReady($table['tabledata']['server']['port']) ?>" id="table_tabledata_server_port">
                </td>
            </tr>
            <tr>
                <td><label for="table_tabledata_server_user"><?= _("Nutzer") ?></label></td>
                <td>
                    <input type="text" name="table[tabledata][server][user]" value="<?= htmlReady($table['tabledata']['server']['user']) ?>" id="table_tabledata_server_user">
                </td>
            </tr>
            <tr>
                <td><label for="table_tabledata_server_password"><?= _("Passwort") ?></label></td>
                <td>
                    <input type="text" name="table[tabledata][server][password]" value="<?= htmlReady($table['tabledata']['server']['password']) ?>" id="table_tabledata_server_password">
                </td>
            </tr>
            <tr>
                <td><label for="table_tabledata_server_dbname"><?= _("Datenbankname") ?></label></td>
                <td>
                    <input type="text" name="table[tabledata][server][dbname]" value="<?= htmlReady($table['tabledata']['server']['dbname']) ?>" id="table_tabledata_server_dbname">
                </td>
            </tr>
            <tr>
                <td><label for="table_tabledata_server_table"><?= _("Tabellenname") ?></label></td>
                <td>
                    <input type="text" name="table[tabledata][server][table]" value="<?= htmlReady($table['tabledata']['server']['table']) ?>" id="table_tabledata_server_table">
                </td>
            </tr>
        </tbody>
    </table>

    <label>
        <input type="checkbox" name="table[synchronization]" value="1"<?= $table['synchronization'] ? " checked" : "" ?>>
        <?= _("Synchronisierung (Importierte Objekte werden beim Update gelöscht, wenn sie beim neuen Import nicht mit mehr auftauchen)") ?>
    </label>

    <? if ($table->isInDatabase()) : ?>
        <div>
            <?= _("Nur folgende Spalten anzeigen") ?>
            <ul>
                <li>
                    <label>
                        <input type="checkbox" data-proxyfor="input.column_selector"<?= !$table['tabledata']['display_only_columns'] ? " checked" : "" ?>>
                        <?= _("Alle") ?>
                    </label>
                </li>
                <? foreach ($table->getTableHeader() as $column) : ?>
                    <? if ($column !== "IMPORT_TABLE_PRIMARY_KEY") : ?>
                        <li>
                            <label>
                                <input type="checkbox" name="table[tabledata][display_only_columns][]" value="<?= htmlReady($column) ?>" class="column_selector"<?= !$table['tabledata']['display_only_columns'] || in_array($column, $table['tabledata']['display_only_columns']) ? " checked" : "" ?>>
                                <?= htmlReady($column) ?>
                            </label>
                        </li>
                    <? endif ?>
                <? endforeach ?>
            </ul>
        </div>
    <? endif ?>

    <div style="text-align: center" data-dialog-button>
        <?= \Studip\Button::create(_("Speichern")) ?>
    </div>

</form>