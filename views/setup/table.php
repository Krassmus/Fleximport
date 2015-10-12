<form action="<?= PluginEngine::getLink($plugin, array(), "setup/table".($table->isNew() ? "" : "/".$table->getId())) ?>"
      method="post"
      class="studip_form"
      data-dialog>

    <label>
        <?= _("Tabellenname") ?>
        <input type="text" name="table[name]" value="<?= htmlReady($table['name'] ?: "fleximport_") ?>">
    </label>

    <label>
        <?= _("Tabellentyp") ?>
        <select name="table[import_type]" onChange="jQuery('#coursespecials').toggle(this.value === 'course'); ">
            <option value="user"<?= $table['import_type'] === "user" ? " selected" : "" ?>><?= _("Nutzerimport") ?></option>
            <option value="course"<?= $table['import_type'] === "course" ? " selected" : "" ?>><?= _("Veranstaltungsimport") ?></option>
            <option value="member"<?= $table['import_type'] === "member" ? " selected" : "" ?>><?= _("Teilnehmerimport") ?></option>
        </select>
    </label>

    <label>
        <?= _("Import über") ?>
        <select name="table[csv_upload]" onChange="jQuery('#server_settings').toggle(this.value <= 0);">
            <option value="1"<?= $table['csv_upload'] || $table->isNew() ? " selected" : "" ?>><?= _("CSV-Datei") ?></option>
            <option value="0"<?= !$table['csv_upload'] && !$table->isNew() ? " selected" : "" ?>><?= _("Datenbank") ?></option>
        </select>
    </label>

    <table id="server_settings" class="default nohover" style="<?= $table['csv_upload'] || $table->isNew() ? "display: none;" : "" ?>">
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

    <label id="coursespecials" style="<?= $table['import_type'] !== "course" ? "display: none;" : "" ?> ">
        <?= _("Eindeutiger Identifizierer für ein Update") ?>
        <select name="table[tabledata][coursespecials][id]">
            <option value=""><?= _("Kein Update") ?></option>
            <option value="veranstaltungsnummer"<?= $table['tabledata']['coursespecials']['id'] === "veranstaltungsnummer" ? " selected" : "" ?>><?= _("Veranstaltungsnummer") ?></option>
            <option value="name"<?= $table['tabledata']['coursespecials']['id'] === "name" ? " selected" : "" ?>><?= _("Veranstaltungsname") ?></option>
        </select>
    </label>

    <div style="text-align: center" data-dialog-button>
        <?= \Studip\Button::create(_("Speichern")) ?>
    </div>

</form>