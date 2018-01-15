<table class="default">
    <thead>
        <tr>
            <th></th>
            <th>
                <?= _("ID") ?>
            </th>
            <th>
                <?= _("Name") ?>
            </th>
            <? if (in_array($class, array("User", "Course"))) : ?>
                <th></th>
            <? endif ?>
        </tr>
    </thead>
    <tbody>
        <? foreach ($deletables as $itemdata) : ?>
            <tr>
                <? $pk = strpos($itemdata['item_id'], "-") !== false
                    ? explode("-", $itemdata['item_id'])
                    : $itemdata['item_id'];
                $item = new $class($pk) ?>
                <td>
                    <?= Icon::create("trash", "info")->asImg(20, array('class' => "text-bottom", 'title' => _("Wird gelöscht."))) ?>
                </td>
                <td><?= htmlReady($itemdata['item_id']) ?></td>
                <td>
                    <? switch ($class) {
                        case "User":
                            $name = $item->getFullName();
                            break;
                        case "CourseMember":
                            $name = User::find($item['user_id']->getFullName())." - ".Course::find($item['seminar_id']->name);
                            break;
                        case "CourseDate":
                            $name = Course::find($item['range_id']->name).": ".$item->getFullname();
                            break;
                        case "StatusgruppeUser":
                            $name = $item->group->name.": ".$item->user->getFullname();
                            break;
                        default:
                            $name = $item->isField("name") ? $item['name'] : ($item->isField("title") ? $item['title'] : null);
                    } ?>
                    <?= htmlReady($name ?: _("unbekannt")) ?>
                </td>
                <? if (in_array($class, array("User", "Course"))) : ?>
                    <td>
                        <? switch ($class) {
                            case "User":
                                $link = URLHelper::getLink("dispatch.php/admin/user/edit/".$itemdata['item_id']);
                                break;
                            case "Course":
                                $link = URLHelper::getLink("dispatch.php/course/details", array('sem_id' => $itemdata['item_id']));
                                break;
                            case "CourseMember":
                                $link = URLHelper::getLink("dispatch.php/course/members", array('cid' => $item['seminar_id']));
                                break;
                            case "CourseDate":
                                $link = URLHelper::getLink("dispatch.php/course/dates", array('cid' => $item['range_id']));
                                break;
                            case "Statusgruppen":
                                $link = URLHelper::getLink("dispatch.php/course/statusgroups", array('cid' => $item['range_id']));
                                break;
                            case "Statusgruppen":
                                $link = URLHelper::getLink("dispatch.php/course/statusgroups", array('cid' => $item['range_id']));
                                break;
                        } ?>
                        <a href="<?= $link ?>" target="_blank">
                            <?= Icon::create("arr_2right", "clickable")->asImg(20, array('class' => "text-bottom", 'title' => _("Zum Objekt."))) ?>
                        </a>
                    </td>
                <? endif ?>
            </tr>
        <? endforeach ?>
    </tbody>
</table>