<form action="<?= PluginEngine::getLink($plugin, array(), "import/process") ?>"
      method="post"
      enctype="multipart/form-data">

    <? foreach ($tables as $table) : ?>
        <? if ($table->isInDatabase()) : ?>
            <table class="default">
                <caption>
                    <div class="caption-container">
                        <div class="caption-content">
                            <? switch ($table['import_type']) {
                                case "user":
                                    echo Assets::img("icons/20/black/person", array('class' => "text-bottom"));
                                    break;
                                case "member":
                                    echo Assets::img("icons/20/black/group", array('class' => "text-bottom"));
                                    break;
                                case "course":
                                default:
                                    echo Assets::img("icons/20/black/seminar", array('class' => "text-bottom"));
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
                                <?= Assets::img("icons/20/blue/admin") ?>
                            </a>
                            <a href="<?= PluginEngine::getLink($plugin, array(), "setup/table/".$table->getId()) ?>" data-dialog title="<?= _("Tabelleneinstellung bearbeiten") ?>">
                                <?= Assets::img("icons/20/blue/admin") ?>
                            </a>
                            <a href="<?= PluginEngine::getLink($plugin, array(), "setup/removetable/".$table->getId()) ?>" onClick="return window.confirm('<?= _("Wirklich l�schen?") ?>');" title="<?= _("Tabelle l�schen") ?>">
                                <?= Assets::img("icons/20/blue/trash") ?>
                            </a>
                        </div>
                    </div>
                </caption>


                <thead>
                    <tr>
                        <? $tableHeader = $table->getTableHeader() ?>
                        <? foreach ($tableHeader as $column) : ?>
                            <? if ($column !== "IMPORT_TABLE_PRIMARY_KEY") : ?>
                                <th><?= htmlReady($column) ?></th>
                            <? endif ?>
                        <? endforeach ?>
                    </tr>
                </thead>
                <tbody>
                    <? if ($table['display_lines'] !== "ondemand") : ?>
                        <? foreach ($table->fetchLines() as $line) : ?>
                            <tr>
                                <? foreach ($tableHeader as $column) : ?>
                                    <? if ($column !== "IMPORT_TABLE_PRIMARY_KEY") : ?>
                                        <td><?= htmlReady($line[$column]) ?></td>
                                    <? endif ?>
                                <? endforeach ?>
                            </tr>
                        <? endforeach ?>
                    <? endif ?>
                </tbody>
            </table>
        <? else : ?>
            <div>
                <div style="float: right;">
                    <a href="<?= PluginEngine::getLink($plugin, array(), "setup/table/".$table->getId()) ?>" data-dialog>
                        <?= Assets::img("icons/20/blue/admin") ?>
                    </a>
                    <a href="<?= PluginEngine::getLink($plugin, array(), "setup/removetable/".$table->getId()) ?>" onClick="return window.confirm('<?= _("Wirklich l�schen?") ?>');">
                        <?= Assets::img("icons/20/blue/trash") ?>
                    </a>
                </div>
                <h2>
                    <? switch ($table['import_type']) {
                        case "user":
                            echo Assets::img("icons/20/black/person", array('class' => "text-bottom"));
                            break;
                        case "member":
                            echo Assets::img("icons/20/black/group", array('class' => "text-bottom"));
                            break;
                        case "course":
                        default:
                            echo Assets::img("icons/20/black/seminar", array('class' => "text-bottom"));
                            break;
                    } ?>
                    <?= htmlReady($table['name']) ?>
                </h2>

                <? if ($table['source'] === "csv_weblink") : ?>
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
    _("Tabelle hinzuf�gen"),
    PluginEngine::getURL($plugin, array(), "setup/table"),
    Assets::image_path("icons/black/add"),
    array('data-dialog' => 1)
);

Sidebar::Get()->addWidget($actions);