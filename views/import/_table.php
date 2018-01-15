<? if ($table->isInDatabase()) : ?>
    <div id="table_<?= $table->getId() ?>_container" class="tablecontainer" data-name="<?= htmlReady($table['name']) ?>" data-table_id="<?= $table->getId() ?>">
        <?= $this->render_partial("import/_table_content.php", compact("table")) ?>
    </div>
<? else : ?>
    <div style="margin-bottom: 50px;" id="table_<?= $table->getId() ?>_container" class="tablecontainer" data-name="<?= htmlReady($table['name']) ?>" data-table_id="<?= $table->getId() ?>">
        <div style="float: right;">
            <a href="<?= PluginEngine::getLink($plugin, array(), "setup/table/".$table->getId()) ?>" data-dialog>
                <?= Icon::create("admin", "clickable")->asImg(20) ?>
            </a>
            <a href="<?= PluginEngine::getLink($plugin, array(), "setup/removetable/".$table->getId()) ?>" onClick="STUDIP.Dialog.confirm('<?= _("Wirklich die Tabelle löschen?") ?>', function () { STUDIP.Fleximport.deleteTable('<?= $table->getId() ?>') }); return false;">
                <?= Icon::create("trash", "clickable")->asImg(20) ?>
            </a>
        </div>
        <h2>
            <? switch ($table['import_type']) {
                case "User":
                    echo Icon::create("person", "info")->asImg(20, array('class' => "text-bottom"));
                    break;
                case "CourseMember":
                    echo Icon::create("group2", "info")->asImg(20, array('class' => "text-bottom"));
                    break;
                case "CourseDate":
                    echo Icon::create("date", "info")->asImg(20, array('class' => "text-bottom"));
                    break;
                case "Statusgruppen":
                    echo Icon::create("group3", "info")->asImg(20, array('class' => "text-bottom"));
                    break;
                case "Course":
                    echo Icon::create("seminar", "info")->asImg(20, array('class' => "text-bottom"));
                    break;
                case "fleximport_mysql_command":
                    echo Icon::create("unit-test", "info")->asImg(20, array('class' => "text-bottom"));
                    break;
                case "":
                    echo Icon::create("remove-circle", "info")->asImg(20, array('class' => "text-bottom"));
                    break;
                default:
                    echo Icon::create("doit", "info")->asImg(20, array('class' => "text-bottom", 'title' => $table['import_type'] ? sprintf(_("Es werden %s-Objekte importiert."), $table['import_type']) : _("Hilfstabelle wird nicht direkt importiert.")));
                    break;
            } ?>
            <?= htmlReady($table['name']) ?>
            <? if ($table->getPlugin()) : ?>
                <? $description = $table->getPlugin()->getDescription() ?>
                <?= Icon::create("plugin", "inactive")->asImg(13, array('class' => "text-bottom", 'title' => $description ? _("Diese Tabelle wird von einem Plugin unterstützt, das folgendes macht: ").$description : _("Diese Tabelle wird von einem Plugin unterstützt."))) ?>
            <? endif ?>
        </h2>

        <? if ($table['source'] === "csv_upload") : ?>
            <label style="cursor: pointer;">
                <?= Icon::create("upload", "clickable")->asImg(40, array('class' => "text-bottom")) ?>
                <?= _("CSV-Datei hochladen") ?>
                <input type="file" name="tableupload[<?= $table->getId() ?>]" onChange="jQuery(this).closest('form').submit();" style="display: none;">
            </label>
        <? endif ?>
    </div>
<? endif ?>



