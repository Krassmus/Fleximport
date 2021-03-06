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
        <?= Icon::create("link-intern", "clickable")->asImg(16, array('class' => "text-bottom")) ?>
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
            <? $overwrite = isset($data[$field]) && ($data[$field] !== false) && (!in_array($field, (array) $table['tabledata']['ignoreonupdate']) || $object->isNew()) ?>
            <tr<?= $overwrite ? "" : ' style="opacity: 0.5;"' ?>>
                <? if (!$object->isNew()) : ?>
                <td>
                    <? if ($overwrite && ($object[$field] != $data[$field])) : ?>
                        <?= Icon::create("arr_2right", "inactive")->asImg(20, array('class' => "text-bottom", 'title' => _("Es gibt Veränderungen in diesem Feld."))) ?>
                    <? endif ?>
                </td>
                <? endif ?>
                <td style="font-family: MONOSPACE;">
                    <?= htmlReady($field) ?>
                </td>
                <? if (!$object->isNew()) : ?>
                    <td><?= htmlReady($object[$field]) ?></td>
                <? endif ?>
                <td><?
                    if (!$overwrite) {
                        echo Icon::create("decline", "inactive")->asImg(16, array('title' => _("Wert wird nicht überschrieben.")));
                    } else {
                        echo htmlReady($data[$field]);
                    } ?></td>
            </tr>
        <? endforeach ?>
        <? foreach ($datafields as $datafield) : ?>
            <? $datafieldname = is_string($datafield['name']) ? $datafield['name'] : $datafield['name']->original() ?>
            <? $overwrite = isset($data[$datafieldname]) && ($data[$datafieldname] !== false) ?>
            <tr<?= $overwrite ? "" : ' style="opacity: 0.5;"' ?>>
                <? if (!$object->isNew()) : ?>
                    <td>
                        <?
                        $id = array($datafield->getId());
                        foreach (array_reverse((array) $object->getId()) as $id_part) {
                            $id[] = $id_part;
                        }
                        if (count($id) < 3) {
                            $id[] = "";
                        }
                        if (StudipVersion::newerThan("4.0") && count($id) < 4) {
                            $id[] = "";
                        }
                        $entry = new DatafieldEntryModel($id);
                        $oldvalue = $entry->content;
                        ?>
                        <? if ($overwrite && ($oldvalue != $data[$datafield['name']])) : ?>
                            <?= Icon::create("arr_2right", "inactive")->asImg(20, array('class' => "text-bottom", 'title' => _("Es gibt Veränderungen in diesem Feld."))) ?>
                        <? endif ?>
                    </td>
                <? endif ?>
                <td style="font-family: MONOSPACE;">
                    <?= htmlReady($datafield['name']) ?>
                </td>
                <? if (!$object->isNew()) : ?>
                    <td><?= htmlReady($oldvalue) ?></td>
                <? endif ?>
                <td>
                    <? if (!$overwrite) : ?>
                        <?= Icon::create("decline", "inactive")->asImg(16, array('title' => _("Wert wird nicht überschrieben."))) ?>
                    <? else : ?>
                        <?= htmlReady($data[$datafield['name']]) ?>
                    <? endif ?>
                </td>
            </tr>
        <? endforeach ?>
        <? foreach ((array) $resourceproperties as $property) : ?>
            <? $overwrite = isset($data[$property['name']]) && ($data[$property['name']] !== false) ?>
            <tr<?= $overwrite ? "" : ' style="opacity: 0.5;"' ?>>
                <? if (!$object->isNew()) : ?>
                    <td>
                        <?
                        $entry = new ResourceProperty([$object->getId(), $property['name']->getId()]);
                        $oldvalue = $entry->state;
                        ?>
                        <? if ($overwrite && ($oldvalue != $data[$property['name']])) : ?>
                            <?= Icon::create("arr_2right", "inactive")->asImg(20, array('class' => "text-bottom", 'title' => _("Es gibt Veränderungen in diesem Feld."))) ?>
                        <? endif ?>
                    </td>
                <? endif ?>
                <td style="font-family: MONOSPACE;">
                    <?= htmlReady($property['name']) ?>
                </td>
                <? if (!$object->isNew()) : ?>
                    <td><?= htmlReady($oldvalue) ?></td>
                <? endif ?>
                <td>
                    <? if (!$overwrite) : ?>
                        <?= Icon::create("decline", "inactive")->asImg(16, array('title' => _("Wert wird nicht überschrieben."))) ?>
                    <? else : ?>
                        <?= htmlReady($data[$property['name']]) ?>
                    <? endif ?>
                </td>
            </tr>
        <? endforeach ?>
        <? foreach ((array) $additional_fields as $field => $currentValue) : ?>
            <tr>
                <? if (!$object->isNew()) : ?>
                <td>
                    <? if (!$object->isNew()) : ?>
                        <?
                        $changed = false;
                        if ($currentValue !== false) {
                            if (is_array($currentValue)) {
                                $changed = count(array_diff($data[$field], $currentValue))
                                    + ($table['tabledata']['simplematching'][$field]['sync'] ? count(array_diff($currentValue, $data[$field])) : 0)
                                    > 0;
                            } else {
                                $changed = $data[$field] != $currentValue;
                            }
                        } ?>
                        <? if ($changed) : ?>
                            <?= Icon::create("arr_2right", "inactive")->asImg(20, array('class' => "text-bottom", 'title' => _("Es gibt Veränderungen in diesem Feld."))) ?>
                        <? endif?>
                    <? endif ?>
                </td>
                <? endif ?>
                <td style="font-family: MONOSPACE;">
                    <?= htmlReady($field) ?>
                </td>
                <? if (!$object->isNew()) : ?>
                    <td>
                        <? if ($currentValue !== false) : ?>
                            <? if (is_array($currentValue)) : ?>
                                <ul style="padding-left: 15px;">
                                    <? foreach ($currentValue as $value) : ?>
                                        <li><?= htmlReady($value) ?></li>
                                    <? endforeach ?>
                                </ul>
                            <? else : ?>
                                <?= htmlReady($currentValue) ?>
                            <? endif ?>
                        <? else : ?>

                        <? endif ?>
                    </td>
                <? endif ?>
                <td>
                    <? if (is_array($data[$field])) : ?>
                        <ul style="padding-left: 18px;">
                            <? foreach ($data[$field] as $value) : ?>
                                <li><?= htmlReady($value) ?></li>
                            <? endforeach ?>
                        </ul>
                    <? else : ?>
                    <?= htmlReady($data[$field]) ?>
                    <? endif ?>
                </td>
            </tr>
        <? endforeach ?>
    </tbody>
</table>
