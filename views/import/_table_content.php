<? $limit = ($limit === false) || ($limit > 0) ? $limit : (FleximportConfig::get("FLEXIMPORT_DISPLAY_LINES") ?: 20) ?>
<? $count = $table->fetchCount() ?>
<? $displayed_lines = 0 ?>
<table class="default" style="margin-bottom: 50px;" id="<?= $table->getId() ?>">
    <caption>
        <div class="caption-container">
            <div class="caption-content">
                <? switch ($table['import_type']) {
                    case "User":
                        echo version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
                            ? Icon::create("person", "info")->asImg(20, array('class' => "text-bottom", 'title' => _("Es werden Nutzer import.")))
                            : Assets::img("icons/20/black/person", array('class' => "text-bottom", 'title' => _("Es werden Nutzer import.")));
                        break;
                    case "CourseMember":
                        echo version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
                            ? Icon::create("group2", "info")->asImg(20, array('class' => "text-bottom", 'title' => _("Es werden Teilnehmer an veranstaltungen import.")))
                            : Assets::img("icons/20/black/group2", array('class' => "text-bottom", 'title' => _("Es werden Teilnehmer an veranstaltungen import.")));
                        break;
                    case "CourseDate":
                        echo version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
                            ? Icon::create("date", "info")->asImg(20, array('class' => "text-bottom"))
                            : Assets::img("icons/20/black/date", array('class' => "text-bottom"));
                        break;
                    case "Statusgruppen":
                        echo version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
                            ? Icon::create("group3", "info")->asImg(20, array('class' => "text-bottom"))
                            : Assets::img("icons/20/black/group3", array('class' => "text-bottom"));
                        break;
                    case "Course":
                        echo version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
                            ? Icon::create("seminar", "info")->asImg(20, array('class' => "text-bottom", 'title' => _("Es werden Veranstaltungen import.")))
                            : Assets::img("icons/20/black/seminar", array('class' => "text-bottom", 'title' => _("Es werden Veranstaltungen import.")));
                        break;
                    case "":
                        echo version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
                            ? Icon::create("remove-circle", "info")->asImg(20, array('class' => "text-bottom", 'title' => _("Dies ist eine Hilfstabelle und wird nicht für sich importiert.")))
                            : Assets::img("icons/20/black/remove-circle", array('class' => "text-bottom", 'title' => _("Dies ist eine Hilfstabelle und wird nicht für sich importiert.")));
                        break;
                    default:
                        echo version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
                            ? Icon::create("doit", "info")->asImg(20, array('class' => "text-bottom", 'title' => $table['import_type'] ? sprintf(_("Es werden %s-Objekte importiert."), $table['import_type']) : _("Hilfstabelle wird nicht direkt importiert.")))
                            : Assets::img("icons/20/black/doit", array('class' => "text-bottom", 'title' => $table['import_type'] ? sprintf(_("Es werden %s-Objekte importiert."), $table['import_type']) : _("Hilfstabelle wird nicht direkt importiert.")));
                        break;
                } ?>
                <?= htmlReady($table['name']) ?>
                <div class="caption-subtext" style="font-size: 0.6em; display: inline;">(<?= sprintf("%s Einträge", $count) ?>)</div>
                <? if ($table->getPlugin()) : ?>
                    <? $description = $table->getPlugin()->getDescription() ?>
                    <?= version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
                        ? Icon::create("plugin", "inactive")->asImg(13, array('class' => "text-bottom", 'title' => $description ? _("Diese Tabelle wird von einem Plugin unterstützt, das folgendes macht: ").$description : _("Diese Tabelle wird von einem Plugin unterstützt.")))
                        : Assets::img("icons/13/grey/plugin", array('class' => "text-bottom", 'title' => $description ? _("Diese Tabelle wird von einem Plugin unterstützt, das folgendes macht: ").$description : _("Diese Tabelle wird von einem Plugin unterstützt."))) ?>
                <? endif ?>
            </div>
            <div class="caption-actions">
                <? if ($table['source'] === "csv_upload" && !$table->customImportEnabled()) : ?>
                    <label style="cursor: pointer;" title="<?= _("CSV-Datei hochladen") ?>">
                        <?= version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
                            ? Icon::create("upload", "clickable")->asImg(20)
                            : Assets::img("icons/20/blue/upload") ?>
                        <input type="file" name="tableupload[<?= $table->getId() ?>]" onChange="jQuery(this).closest('form').submit();" style="display: none;">
                    </label>
                <? endif ?>
                <? if ($table->isInDatabase()) : ?>
                    <a href="<?= PluginEngine::getLink($plugin, array('secret' => $table->getExportSecret()), "export/export/".$table->getId()) ?>" title="<?= _("Als CSV-Datei herunterladen") ?>">
                        <?= version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
                            ? Icon::create("download", "clickable")->asImg(20)
                            : Assets::img("icons/20/blue/download") ?>
                    </a>
                <? endif ?>
                <? if ($table['import_type'] && !in_array($table['import_type'], array("fleximport_mysql_command"))) : ?>
                    <a href="<?= PluginEngine::getLink($plugin, array(), "setup/tablemapping/".$table->getId()) ?>" data-dialog title="<?= _("Datenmapping einstellen") ?>">
                        <?= version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
                            ? Icon::create("group", "clickable")->asImg(20)
                            : Assets::img("icons/20/blue/group") ?>
                    </a>
                <? endif ?>
                <a href="<?= PluginEngine::getLink($plugin, array(), "setup/table/".$table->getId()) ?>" data-dialog title="<?= _("Tabelleneinstellung bearbeiten") ?>">
                    <?= version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
                        ? Icon::create("admin", "clickable")->asImg(20)
                        : Assets::img("icons/20/blue/admin") ?>
                </a>
                <a href="<?= PluginEngine::getLink($plugin, array(), "setup/removetable/".$table->getId()) ?>" onClick="STUDIP.Dialog.confirm('<?= _("Wirklich die Tabelle löschen?") ?>', function () { STUDIP.Fleximport.deleteTable('<?= $table->getId() ?>') }); return false;">
                    <?= version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
                        ? Icon::create("trash", "clickable")->asImg(20)
                        : Assets::img("icons/20/blue/trash") ?>
                </a>
            </div>
        </div>
    </caption>


    <thead>
    <tr>
        <th></th>
        <th></th>
        <? $tableHeader = $table->getTableHeader() ?>
        <? foreach ($tableHeader as $column) : ?>
            <? if ($column !== "IMPORT_TABLE_PRIMARY_KEY" && (!$table['tabledata']['display_only_columns'] || in_array($column, $table['tabledata']['display_only_columns']))) : ?>
                <th><?= htmlReady($column) ?></th>
            <? endif ?>
        <? endforeach ?>
    </tr>
    </thead>
    <tbody>
    <? $item_ids = array() ?>
    <? if ($table['display_lines'] !== "ondemand") : ?>
        <? foreach ($table->fetchLines() as $line) : ?>
            <? $report = $table->checkLine($line);
            if ($report['pk'] && !$report['errors']) {
                $item_ids[] = is_array($report['pk']) ? implode("-", $report['pk']) : $report['pk'];
            }
            if (($displayed_lines >= (int) $limit) && ($limit !== false)) {
                break;
            }
            if (($count < (int) $limit || $report['errors']) || $limit === false) : ?>
                <tr>
                    <td>
                        <? if ($table['import_type']) : ?>
                            <a href="<?= PluginEngine::getLink($plugin, array('table' => $table['name']), "import/targetdetails/".$line['IMPORT_TABLE_PRIMARY_KEY']) ?>" data-dialog>
                                <? $icon = $report['found'] ? "accept" : "star" ?>
                                <? if ($report['errors']) : ?>
                                    <?= version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
                                            ? Icon::create($icon, "navigation")->asImg(20, array('title' => $report['found'] ? _("Datensatz wurde in Stud.IP gefunden") : _("Objekt würde neu angelegt werden. Zur Datenvorschau.")))
                                            : Assets::img("icons/20/lightblue/".$icon, array('title' => $report['found'] ? _("Datensatz wurde in Stud.IP gefunden") : _("Objekt würde neu angelegt werden. Zur Datenvorschau."))) ?>
                                <? else :?>
                                    <?= version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
                                        ? Icon::create($icon, "clickable")->asImg(20, array('title' => $report['found'] ? _("Datensatz wurde in Stud.IP gefunden und wird geupdated") : _("Objekt wird neu angelegt werden. Zur Datenvorschau.")))
                                        : Assets::img("icons/20/blue/".$icon, array('title' => $report['found'] ? _("Datensatz wurde in Stud.IP gefunden und wird geupdated") : _("Objekt wird neu angelegt werden. Zur Datenvorschau."))) ?>
                                <? endif ?>
                            </a>
                        <? endif ?>
                    </td>
                    <td>
                        <? if ($report['errors']) : ?>
                            <?= version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
                                ? Icon::create("decline", "attention")->asImg(20, array('title' => $report['errors']))
                                : Assets::img("icons/20/red/decline", array('title' => $report['errors'])) ?>
                        <? endif ?>
                    </td>
                    <? foreach ($tableHeader as $column) : ?>
                        <? if ($column !== "IMPORT_TABLE_PRIMARY_KEY" && (!$table['tabledata']['display_only_columns'] || in_array($column, $table['tabledata']['display_only_columns']))) : ?>
                            <td><?= htmlReady($line[$column]) ?></td>
                        <? endif ?>
                    <? endforeach ?>
                </tr>
                <? $displayed_lines++ ?>
            <? endif ?>
        <? endforeach ?>
        <? if ($count > $displayed_lines) : ?>
            <tr>
                <td colspan="<?= count($tableHeader) + 2 ?>">
                    <?= sprintf("%s weitere Zeilen in der Datenbank.", $count - $displayed_lines) ?>
                    <a href="#" onClick="jQuery('#table_<?= $table->getId() ?>_container').load(STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/fleximport/import/showtable/<?= $table->getId() ?>'); jQuery('#load_table_<?= $table->getId() ?>').show(); jQuery(this).hide(); return false;">
                        <?= _("Alle anzeigen") ?>
                    </a>
                    <?= Assets::img("ajax-indicator-black.svg", array('style' => "display: none; height: 16px;", 'id' => "load_table_".$table->getId())) ?>
                </td>
            </tr>
        <? endif ?>
    <? endif ?>
    </tbody>
    <? if ($table['synchronization']) : ?>
        <tfoot>
            <tr>
                <td colspan="100">
                    <? $deletable_items = $table->countDeletableItems($item_ids) ?>
                    <? if ($deletable_items > 0) : ?>
                        <a href="<?= PluginEngine::getLink($plugin, array(), "import/deletables/".$table->getId()) ?>" data-dialog>
                    <? endif ?>
                    <?= sprintf("Synchronisation: %s Datensätze werden bei diesem Import gelöscht.", $deletable_items) ?>
                    <? if ($deletable_items > 0) : ?>
                        </a>
                    <? endif ?>
                </td>
            </tr>
        </tfoot>
    <? endif ?>
</table>