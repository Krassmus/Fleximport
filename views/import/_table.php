<? if ($table->isInDatabase()) : ?>
    <div id="table_<?= $table->getId() ?>_container" class="tablecontainer" data-name="<?= htmlReady($table['name']) ?>" data-table_id="<?= $table->getId() ?>">
        <?= $this->render_partial("import/_table_content.php", compact("table")) ?>
    </div>
<? else : ?>
    <div style="margin-bottom: 50px;" id="table_<?= $table->getId() ?>_container" class="tablecontainer" data-name="<?= htmlReady($table['name']) ?>" data-table_id="<?= $table->getId() ?>">
        <div style="float: right;">
            <a href="<?= PluginEngine::getLink($plugin, array(), "setup/table/".$table->getId()) ?>" data-dialog>
                <?= Assets::img("icons/20/blue/admin") ?>
            </a>
            <a href="<?= PluginEngine::getLink($plugin, array(), "setup/removetable/".$table->getId()) ?>" onClick="STUDIP.Dialog.confirm('<?= _("Wirklich die Tabelle löschen?") ?>', function () { STUDIP.Fleximport.deleteTable('<?= $table->getId() ?>') }); return false;">
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
            <? if ($table->getPlugin()) : ?>
                <? $description = $table->getPlugin()->getDescription() ?>
                <?= Assets::img("icons/13/grey/plugin", array('class' => "text-bottom", 'title' => $description ? _("Diese Tabelle wird von einem Plugin unterstützt, das folgendes macht: ").$description : _("Diese Tabelle wird von einem Plugin unterstützt."))) ?>
            <? endif ?>
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


