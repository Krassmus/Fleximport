<? $limit = ($limit === false) || ($limit > 0) ? $limit : 30 ?>
<? $count = $table->fetchCount() ?>
<? $displayed_lines = 0 ?>
<table class="default" style="margin-bottom: 50px;" id="<?= $table->getId() ?>">
    <caption>
        <div class="caption-container">
            <div class="caption-content">
                <? switch ($table['import_type']) {
                    case "User":
                        echo Assets::img("icons/20/black/person", array('class' => "text-bottom", 'title' => _("Es werden Nutzer import.")));
                        break;
                    case "CourseMember":
                        echo Assets::img("icons/20/black/group2", array('class' => "text-bottom", 'title' => _("Es werden Teilnehmer an veranstaltungen import.")));
                        break;
                    case "Course":
                        echo Assets::img("icons/20/black/seminar", array('class' => "text-bottom", 'title' => _("Es werden Veranstaltungen import.")));
                        break;
                    case "":
                        echo Assets::img("icons/20/black/remove-circle", array('class' => "text-bottom", 'title' => _("Dies ist eine Hilfstabelle und wird nicht für sich importiert.")));
                        break;
                    default:
                        echo Assets::img("icons/20/black/doit", array('class' => "text-bottom", 'title' => $table['import_type'] ? sprintf(_("Es werden %s-Objekte importiert."), $table['import_type']) : _("Hilfstabelle wird nicht direkt importiert.")));
                        break;
                } ?>
                <?= htmlReady($table['name']) ?>
                <div class="caption-subtext" style="font-size: 0.6em; display: inline;">(<?= sprintf("%s Einträge", $count) ?>)</div>
                <? if ($table->getPlugin()) : ?>
                    <? $description = $table->getPlugin()->getDescription() ?>
                    <?= Assets::img("icons/13/grey/plugin", array('class' => "text-bottom", 'title' => $description ? _("Diese Tabelle wird von einem Plugin unterstützt, das folgendes macht: ").$description : _("Diese Tabelle wird von einem Plugin unterstützt."))) ?>
                <? endif ?>
            </div>
            <div class="caption-actions">
                <? if ($table['source'] === "csv_upload" && !$table->customImportEnabled()) : ?>
                    <label style="cursor: pointer;" title="<?= _("CSV-Datei hochladen") ?>">
                        <?= Assets::img("icons/20/blue/upload") ?>
                        <input type="file" name="tableupload[<?= $table->getId() ?>]" onChange="jQuery(this).closest('form').submit();" style="display: none;">
                    </label>
                <? endif ?>
                <a href="<?= PluginEngine::getLink($plugin, array(), "setup/tablemapping/".$table->getId()) ?>" data-dialog title="<?= _("Datenmapping einstellen") ?>">
                    <?= Assets::img("icons/20/blue/group") ?>
                </a>
                <a href="<?= PluginEngine::getLink($plugin, array(), "setup/table/".$table->getId()) ?>" data-dialog title="<?= _("Tabelleneinstellung bearbeiten") ?>">
                    <?= Assets::img("icons/20/blue/admin") ?>
                </a>
                <a href="<?= PluginEngine::getLink($plugin, array(), "setup/removetable/".$table->getId()) ?>" onClick="return window.confirm('<?= _("Wirklich löschen?") ?>');" title="<?= _("Tabelle löschen") ?>">
                    <?= Assets::img("icons/20/blue/trash") ?>
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
    <? if ($table['display_lines'] !== "ondemand") : ?>
        <? foreach ($table->fetchLines() as $line) : ?>
            <? if (($displayed_lines >= (int) $limit) && ($limit !== false)) {
                break;
            } ?>
            <? $report = $table->checkLine($line) ?>
            <? if (($count < (int) $limit || $report['errors']) || $limit === false) : ?>
                <tr>
                    <td>
                        <a href="<?= PluginEngine::getLink($plugin, array('table' => $table['name']), "import/targetdetails/".$line['IMPORT_TABLE_PRIMARY_KEY']) ?>" data-dialog>
                            <? $icon = $report['found'] ? "accept" : "star" ?>
                            <? if ($report['errors']) : ?>
                                <?= Assets::img("icons/20/lightblue/".$icon, array('title' => $report['found'] ? _("Datensatz wurde in Stud.IP gefunden") : _("Datenvorschau"))) ?>
                            <? else :?>
                                <?= Assets::img("icons/20/blue/".$icon, array('title' => $report['found'] ? _("Datensatz wurde in Stud.IP gefunden und wird geupdated") : _("Datenvorschau der Daten, die neu angelegt werden würden."))) ?>
                            <? endif ?>
                        </a>
                    </td>
                    <td>
                        <? if ($report['errors']) : ?>
                            <?= Assets::img("icons/20/red/decline", array('title' => $report['errors'])) ?>
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
</table>