<form
    action="<?= PluginEngine::getLink($plugin, array(), "setup/table" . ($table->isNew() ? "" : "/" . $table->getId())) ?>"
    method="post"
    class="<?= Fleximport::getCSSFormClass() ?>"
    data-dialog xmlns="http://www.w3.org/1999/html">

    <input type="hidden" name="table[process_id]" value="<?= htmlReady($table['process_id']) ?>">
    <input type="hidden" name="process_id" value="<?= htmlReady($table['process_id']) ?>">

    <label>
        <?= _("Tabellenname") ?>
        <input type="text" name="table[name]" value="<?= htmlReady($table['name'] ?: "fleximport_") ?>">
    </label>

    <label>
        <?= _("Zweck der Tabelle") ?>
        <select name="table[import_type]" onChange="jQuery('#other_import_type').toggle(this.value === 'other'); jQuery('#fleximport_mysql_command').toggle(this.value === 'fleximport_mysql_command'); ">
            <option value="User"<?= $table['import_type'] === "User" ? " selected" : "" ?>><?= _("Nutzerimport") ?></option>
            <option value="Course"<?= $table['import_type'] === "Course" ? " selected" : "" ?>><?= _("Veranstaltungsimport") ?></option>
            <option value="CourseMember"<?= $table['import_type'] === "CourseMember" ? " selected" : "" ?>><?= _("Teilnehmerimport") ?></option>
            <option value="Statusgruppen"<?= $table['import_type'] === "Statusgruppen" ? " selected" : "" ?>><?= _("Statusgruppenimport") ?></option>
            <option value="CourseDate"<?= $table['import_type'] === "CourseDate" ? " selected" : "" ?>><?= _("Veranstaltungstermine") ?></option>
            <option value=""<?= !$table['import_type'] && !$table->isNew() ? " selected" : "" ?>><?= _("Tabelle nicht importieren") ?></option>
            <option value="fleximport_mysql_command"<?= $table['import_type'] === "fleximport_mysql_command" ? " selected" : "" ?>><?= _("MySQL-Anweisung") ?></option>
            <option value="other"<?= !$table->isNew() && $table['import_type'] && !in_array($table['import_type'], array("User", "CourseMember", "Course", "CourseDate", "fleximport_mysql_command")) ? " selected" : "" ?>><?= _("SORM-Objekt") ?></option>
        </select>
        <div id="other_import_type" style="<?= !$table->isNew() && $table['import_type'] && !in_array($table['import_type'], array("User", "CourseMember", "Course", "CourseDate", "fleximport_mysql_command")) ? "" : "display: none; " ?>">
            <input type="text" name="other_import_type" value="<?= !$table->isNew() && $table['import_type'] && !in_array($table['import_type'], array("User", "CourseMember", "Course", "CourseDate", "fleximport_mysql_command")) ? htmlReady($table['import_type']) : "" ?>" placeholder="<?= _("Name der SORM-Klasse") ?>">
        </div>
        <div id="fleximport_mysql_command" style="<?= $table['import_type'] === "fleximport_mysql_command" ? "" : "display: none; " ?>">
            <textarea name="table[tabledata][fleximport_mysql_command]"
                      placeholder="<?= _("INSERT IGNORE INTO ....") ?>"
                      style="font-family: Monospace; font-size: 0.8em; min-height: 12em;"
            ><?= htmlReady($table['tabledata']['fleximport_mysql_command']) ?></textarea>
        </div>
    </label>

    <? if ($table->isNew() || !$table->getPlugin() || !$table->getPlugin()->customImportEnabled()) : ?>
        <label>
            <?= _("Import über") ?>
            <select name="table[source]" onChange="jQuery('#server_settings').toggle(this.value == 'database'); jQuery('#weblink_info').toggle(this.value == 'csv_weblink'); jQuery('#sqlview_info').toggle(this.value == 'sqlview'); jQuery('#csv_studipfile_info').toggle(this.value == 'csv_studipfile'); jQuery('#csv_encoding').toggle(['csv_studipfile','csv_upload','csv_weblink'].indexOf(this.value) !== -1);">
                <option value="csv_upload"<?= $table['source'] === "csv_upload" || $table->isNew() ? " selected" : "" ?>><?= _("CSV-Upload") ?></option>
                <option value="csv_studipfile"<?= $table['source'] === "csv_studipfile" ? " selected" : "" ?>><?= _("CSV-Datei in Stud.IP") ?></option>
                <option value="csv_weblink"<?= $table['source'] === "csv_weblink" ? " selected" : "" ?>><?= _("CSV-Internetquelle") ?></option>
                <option value="database"<?= $table['source'] === "database" ? " selected" : "" ?>><?= _("Datenbank") ?></option>
                <option value="extern"<?= $table['source'] === "extern" ? " selected" : "" ?>><?= _("Externes Tool") ?></option>
                <option value="sqlview"<?= $table['source'] === "sqlview" ? " selected" : "" ?>><?= _("SQL-View") ?></option>
            </select>
        </label>
    <? endif ?>

    <label id="csv_encoding" style="<?= !in_array($table['source'], array("csv_upload", "csv_studipfile", "csv_weblink")) ? "display: none;" : "" ?>">
        <?= _("Zeichensatz der Datei") ?>
        <select name="table[tabledata][source_encoding]">
            <option value="utf8"<?= $table['tabledata']['source_encoding'] === "utf8" ? " selected" : "" ?>>UTF-8</option>
            <option value="windows-1252"<?= $table['tabledata']['source_encoding'] === "windows-1252" ? " selected" : "" ?>>windows-1252</option>
        </select>
    </label>

    <label id="csv_studipfile_info" style="<?= $table['source'] !== "csv_studipfile" ? "display: none;" : "" ?>">
        <?= _("Datei-ID der Datei in Stud.IP") ?>
        <input type="text" name="table[tabledata][weblink][file_id]" value="<?= htmlReady($table['tabledata']['weblink']['file_id']) ?>">
    </label>

    <label id="weblink_info" style="<?= $table['source'] !== "csv_weblink" ? "display: none;" : "" ?>">
        <?= _("URL der CSV-Datei") ?>
        <input type="text" name="table[tabledata][weblink][url]" value="<?= htmlReady($table['tabledata']['weblink']['url']) ?>">
    </label>

    <label id="sqlview_info" style="<?= $table['source'] !== "sqlview" ? "display: none;" : "" ?>">
        <?= _("SELECT-Statement") ?>
        <textarea style="font-family: Monospace; font-size: 0.8em; min-height: 12em;"
                  placeholder="SELECT * FROM ...."
                  name="table[tabledata][sqlview][select]"><?= htmlReady($table['tabledata']['sqlview']['select']) ?></textarea>
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

    <label data-change_hash="<?= htmlReady($table->isInDatabase() ? $table->calculateChangeHash() : "") ?>">
        <?= _("Bei Änderungen an den Tabellendaten einen Webhook an folgende URL verschicken (mehrere durch Leerzeichen trennen)") ?>
        <input type="text" name="table[webhook_urls]" value="<?= htmlReady($table['webhook_urls']) ?>" placeholder="https://...">
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
        <? if ($table['synchronization'] && FleximportMappedItem::countBySQL("table_id = ?", array($table->getId())) > 0) : ?>
            <?= \Studip\Button::create(_("Sync-Info Löschen"), 'delete_mapped_items', array('onclick' => "return window.confirm('"._("Wirklich die Informationen über bereits importierte Einträge löschen?")."');")) ?>
        <? endif ?>
    </div>

</form>