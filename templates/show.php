<style>
table.fine {
    border-collapse: collapse;
    margin-left: auto;
    margin-right: auto;
    background-color: white;
}
table.fine > thead {
    width: 100%
}
table.fine > thead > tr > th, table.fine > thead > tr > td {
    padding: 4px;
    padding-left: 20px;
    padding-right: 20px;
}
table.fine > thead > tr > th.headerSortUp {
    background: url('../../assets/images/icons/16/yellow/arr_1up.png') no-repeat right 3px,
                url('../../assets/images/steelgraudunkel.gif');
}
table.fine > thead > tr > th.headerSortDown {
    background: url('../../assets/images/icons/16/yellow/arr_1down.png') no-repeat right 3px,
                url('../../assets/images/steelgraudunkel.gif');
}
table.fine > tbody {
    max-height: 300px;
    overflow: scroll;
}
table.fine > tbody > tr {
    cursor: pointer;
}
table.fine > tbody > tr > td {
    border: thin dotted #dddddd;
    padding: 3px;
}
table.fine > tbody > tr:hover > td {
    background-color: #eeeeee;
}
table.fine > tfoot > tr > td {
    border: thin solid #dddddd;
    padding: 3px;
}
.plugin_info {
    margin: 20px;
    padding: 20px;
    background-color: #e3e3e8;
    background-image: url(<?= $GLOBALS['ABSOLUTE_URI_STUDIP']."plugins_packages/data-quest/nsi_import/assets/background.png" ?>);
    background-position: center center;
    background-repeat: repeat-x;
    border-radius: 10px;
    -moz-border-radius: 10px;
    border: thin solid rgb(200,200,200);
    border-left-color: rgb(210,210,210);
    border-right-color: rgb(210,210,210);
    border-top-color: rgb(220,220,220);
    font-size: 1.2em;
    color: rgb(40,40,40);
    text-shadow: rgba(240,240,240,0.8) 0px 1px 1px;
    box-shadow: inset 0 -1px 1px rgba(0,0,0,0.1),
                inset 0 +3px 3px rgba(255,255,255,0.2);
}
h3 {
    color: rgb(30,30,30);
}
</style>
<? $table_missing = false ?>
<?
if (is_array($msg)) {
    foreach($msg as $one_msg) {
        list($type, $content, $aux) = $one_msg;
        echo call_user_func(array('MessageBox',$type), $content, $aux);
    }
}
?>
<form action="?" method="post" enctype="multipart/form-data" style="display: block; margin-left: auto; margin-right: auto; text-align: center;">
    <p class="plugin_info">
        <?= $description ?>
    </p>
    <? foreach ($db_tables as $db_table => $table_description) : ?>
    <? if ($table_info[$db_table]['fields']) : ?>
    <table class="fine">
        <caption><?= htmlReady($table_description) ?></caption>
        <thead>
            <tr>
                <th></th>
            <? foreach ($table_info[$db_table]['fields'] as $field_name) : ?>
                <? if ($field_name !== "IMPORT_TABLE_PRIMARY_KEY" && !isset($invisible_tables[$db_table][$field_name])) : ?>
                <th><?= htmlReady($field_name) ?></th>
                <? endif ?>
            <? endforeach ?>
            </tr>
        </thead>
        <tbody>
            <? $successfull = 0 ?>
            <? $label = _("Klicken Sie, um die Zeile zu deaktivieren oder wieder zu aktivieren. Sie wird nur mit importiert, wenn sie aktiv ist.") ?>
            <? if (count($table_info[$db_table]['entries'])) : ?>
            <? foreach ($table_info[$db_table]['entries'] as $row) : ?>
            <? $accept = $plugin->checkEntry($db_table, $row); ?>
            <? if ($accept) : ?>
            <tr style="<?= $accept ? "opacity: 0.5;" : "" ?>">
                <td><?= $accept ? Assets::img("icons/16/red/decline.png", array('title' => $accept)) : "" ?></td>
                <? foreach ($table_info[$db_table]['fields'] as $field_name) : ?>
                <? if ($field_name !== "IMPORT_TABLE_PRIMARY_KEY" && !isset($invisible_tables[$db_table][$field_name])) : ?>
                <td><?= isset($map_output[$db_table.".".$field_name]) ? call_user_func($map_output[$db_table.".".$field_name], $row[$field_name]): $row[$field_name] ?></td>
                <? endif ?>
                <? endforeach ?>
            </tr>
            <? else : ?>
                <? $successfull++ ?>
            <? endif ?>
            <? endforeach ?>
            <? else : ?>
            <tr>
                <td colspan="<?= count($table_info[$db_table]['fields']) + 1 ?>" style="text-align: center;"><?= _("Diese Tabelle ist leer.") ?></td>
            </tr>
            <? endif ?>
            <? if ($successfull) : ?>
            <tr>
                <td colspan="<?= count($table_info[$db_table]['fields']) + 1 ?>" style="text-align: center;"><?= sprintf(_("Es werden %s Datensätze nicht angezeigt, weil in ihnen alles in Ordnung ist."), $successfull) ?></td>
            </tr>
            <? endif ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="<?= count($table_info[$db_table]['fields'])+1 ?>">
                <? if ($plugin->upload_enabled()) : ?>
                    <a href="?table=<?= $db_table ?>&reset=1"><?= _("Tabelle löschen und neu hochladen") ?></a>
                <? endif ?>
                </td>
            </tr>
        </tfoot>
    </table>
    <? else : ?>
    <? $table_missing = true ?>
        <h2><?= htmlReady($table_description) ?></h2>
        <? if ($plugin->upload_enabled()) : ?>
        <label>
            <?= sprintf(_("Laden Sie hier die %s-Datei hoch"), $table_description) ?>
            <input type="file" name="<?= $db_table ?>_file">
        </label>
        <div>
            <?= \Studip\Button::create(_("Absenden"), "absenden", array('value' => "1")) ?>
        </div>
        <? else : ?>
        <div class="plugin_info">
        <?= _("Dieses Plugin soll die Daten nicht selbst hochladen, sondern bezieht sie direkt aus der Datenbank. Veranlassen Sie also, dass die Daten in die Datenbank gelangen zum Beispiel über ein anderes Importtool, bevor der Import durchgeführt werden kann.") ?>
        </div>
        <? endif ?>
    <? endif ?>
    <? endforeach ?>
    <? if ($table_missing === false) : ?>
    <div class="plugin_info">
        <?= sprintf(_("Alle Daten durchgeschaut, die roten %se gesehen und kaputte Datensätze notfalls weggeklickt?"), Assets::img("icons/16/red/decline.png")) ?>
        <br>
        <?= ($submit_info instanceof Flexi_Template) ? $submit_info->render()."<br>" : "" ?>
        <?= _("Import jetzt ") ?>
        <?= \Studip\Button::create(_("Starten"), "starten", array('value' => "1")) ?>
    </div>
    <? endif ?>
    
</form>

<script>
jQuery("table.fine > tbody > tr").live("click", function (event) {
    if (jQuery(this).find('input[type=checkbox]').attr('checked')) {
        jQuery(this).css('opacity', '0.5').find('input[type=checkbox]').removeAttr('checked');
    } else {
        jQuery(this).css('opacity', '1').find('input[type=checkbox]').attr('checked', 'checked');
    }
    //return false;
});
jQuery("table.fine > tbody > tr input[type=checkbox]").live("change", function (event) {
    jQuery(this).parents("table.fine > tbody > tr").trigger("click");
    return;
})
jQuery(function () {
    //jQuery('table.fine').tablesorter();
});
</script>