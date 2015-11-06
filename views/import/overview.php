<form action="<?= PluginEngine::getLink($plugin, array(), "import/process") ?>"
      method="post"
      enctype="multipart/form-data">

    <? foreach ($tables as $table) : ?>
        <? if ($table->isInDatabase()) : ?>
            <table class="default" style="margin-bottom: 50px;">
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
                        </div>
                        <div class="caption-actions">
                            <? if ($table['source'] === "csv_upload") : ?>
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
                            <? $report = $table->checkLine($line) ?>
                            <tr>
                                <td>
                                    <? if ($report['found']) : ?>
                                        <a href="<?= PluginEngine::getLink($plugin, array('table' => $table['name']), "import/targetdetails/".$line['IMPORT_TABLE_PRIMARY_KEY']) ?>" data-dialog>
                                            <? if ($report['errors']) : ?>
                                                <?= Assets::img("icons/20/lightblue/accept", array('title' => _("Datensatz wurde in Stud.IP gefunden"))) ?>
                                            <? else :?>
                                                <?= Assets::img("icons/20/blue/accept", array('title' => _("Datensatz wurde in Stud.IP gefunden und wird geupdated"))) ?>
                                            <? endif ?>
                                        </a>
                                    <? endif ?>
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
                        <? endforeach ?>
                    <? endif ?>
                </tbody>
            </table>
        <? else : ?>
            <div style="margin-bottom: 50px;">
                <div style="float: right;">
                    <a href="<?= PluginEngine::getLink($plugin, array(), "setup/table/".$table->getId()) ?>" data-dialog>
                        <?= Assets::img("icons/20/blue/admin") ?>
                    </a>
                    <a href="<?= PluginEngine::getLink($plugin, array(), "setup/removetable/".$table->getId()) ?>" onClick="return window.confirm('<?= _("Wirklich löschen?") ?>');">
                        <?= Assets::img("icons/20/blue/trash") ?>
                    </a>
                </div>
                <h2>
                    <? switch ($table['import_type']) {
                        case "User":
                            echo Assets::img("icons/20/black/person", array('class' => "text-bottom"));
                            break;
                        case "CourseMember":
                            echo Assets::img("icons/20/black/group2", array('class' => "text-bottom"));
                            break;
                        case "Course":
                            echo Assets::img("icons/20/black/seminar", array('class' => "text-bottom"));
                            break;
                        case "":
                            echo Assets::img("icons/20/black/remove-circle", array('class' => "text-bottom"));
                            break;
                        default:
                            echo Assets::img("icons/20/black/doit", array('class' => "text-bottom", 'title' => $table['import_type'] ? sprintf(_("Es werden %s-Objekte importiert."), $table['import_type']) : _("Hilfstabelle wird nicht direkt importiert.")));
                            break;
                    } ?>
                    <?= htmlReady($table['name']) ?>
                </h2>

                <? if ($table['source'] === "csv_upload") : ?>
                    <label style="cursor: pointer;">
                        <?= Assets::img("icons/40/blue/upload", array('class' => "text-bottom")) ?>
                        <?= _("CSV-Datei hochladen") ?>
                        <input type="file" name="tableupload[<?= $table->getId() ?>]" onChange="jQuery(this).closest('form').submit();" style="display: none;">
                    </label>
                <? endif ?>
            </div>
        <? endif ?>
    <? endforeach ?>

    <div style="text-align: center;">
        <?= \Studip\Button::create(_("Import starten"), 'start', array('onClick' => "return window.confirm('"._("Wirklich importieren?")."');")) ?>
    </div>

</form>

<?

$actions = new ActionsWidget();
$actions->addLink(
    _("Tabelle hinzufügen"),
    PluginEngine::getURL($plugin, array(), "setup/table"),
    Assets::image_path("icons/black/add"),
    array('data-dialog' => 1)
);

Sidebar::Get()->addWidget($actions);