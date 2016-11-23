<? if ((in_array($table['import_type'], array("Course", "User", "CourseMember")) || method_exists($object, "getURL")) && !$object->isNew()) : ?>
<div style="text-align: center; padding: 30px;">
    <? switch ($table['import_type']) {
        case "Course":
            $url = "dispatch.php/course/details?sem_id=".$object->getId();
            $text = _("Zur Veranstaltung");
            break;
        case "User":
            $url = "dispatch.php/profile?username=".$object['username'];
            $text = _("Zur Person");
            break;
        case "CourseMember":
            $url = "dispatch.php/course/members?cid=".$object['seminar_id'];
            $text = _("Zur Teilnehmerseite");
            break;
        default:
            $url = $object->getURL();
            $text = _("Zum Objekt");
    } ?>
    <a href="<?= URLHelper::getURL($url) ?>">
        <?= version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
            ? Icon::create("link-intern", "clickable")->asImg(16, array('class' => "text-bottom"))
            : Assets::img("icons/16/blue/link-intern", array('class' => "text-bottom")) ?>
        <?= htmlReady($text) ?>
    </a>
</div>
<? endif ?>

<table class="default">
    <caption>
        <? if (!$object->isNew()) : ?>
            <?= _("Datenvergleich") ?>
        <? else : ?>
            <?= _("Datenübersicht") ?>
        <? endif ?>
    </caption>
    <thead>
        <tr>
            <? if (!$object->isNew()) : ?>
                <th></th>
            <? endif ?>
            <th><?= _("Feldname") ?></th>
            <? if (!$object->isNew()) : ?>
                <th><?= _("Bestehende Daten") ?></th>
            <? endif ?>
            <th><?= _("Zu importierende Daten") ?></th>
        </tr>
    </thead>
    <tbody>
        <? foreach ($table->getTargetFields() as $field) : ?>
            <? $overwrite = isset($data[$field]) && ($data[$field] !== false) && !in_array($field, (array) $table['tabledata']['ignoreonupdate']) ?>
            <tr<?= $overwrite ? "" : ' style="opacity: 0.5;"' ?>>
                <? if (!$object->isNew()) : ?>
                <td>
                    <? if ($overwrite && ($object[$field] !== $data[$field])) : ?>
                        <?= version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
                            ? Icon::create("exclaim", "info")->asImg(20, array('class' => "text-bottom", 'title' => _("Es gibt Veränderungen in diesem Feld.")))
                            : Assets::img("icons/20/black/exclaim", array('class' => "text-bottom", 'title' => _("Es gibt Veränderungen in diesem Feld."))) ?>
                    <? endif ?>
                </td>
                <? endif ?>
                <td style="font-family: MONOSPACE;">
                    <?= htmlReady($field) ?>
                </td>
                <? if (!$object->isNew()) : ?>
                    <td><?= htmlReady($object[$field]) ?></td>
                <? endif ?>
                <td>
                    <? if (!$overwrite) : ?>
                        <?= version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
                            ? Icon::create("decline", "inactive")->asImg(16, array('title' => _("Wert wird nicht überschrieben.")))
                            : Assets::img("icons/16/grey/decline", array('title' => _("Wert wird nicht überschrieben."))) ?>
                    <? else : ?>
                        <?= htmlReady($data[$field]) ?>
                    <? endif ?>
                </td>
            </tr>
        <? endforeach ?>
    </tbody>
</table>