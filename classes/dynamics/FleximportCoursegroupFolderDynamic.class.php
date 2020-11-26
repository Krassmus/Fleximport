<?php

class FleximportCoursegroupFolderDynamic extends FleximportDynamic
{

    public function forClassFields()
    {
        return array(
            'Statusgruppen' => array("fleximport_coursegroup_folder" => _("Bei 1 ist der Ordner da, bei 0 wieder weg."))
        );
    }

    public function isMultiple()
    {
        return false;
    }

    public function applyValue($object, $value, $line, $sync)
    {
        //Lock or unlock course
        if ($value) {
            if (!$object->hasFolder()) {
                create_folder(
                    (_("Dateiordner der Gruppe:") . ' ' . $object['name']),
                    (_("Ablage für Ordner und Dokumente dieser Gruppe")),
                    $object->getId(),
                    15,
                    $object['range_id']
                );
            }
        } else {
            if ($object->hasFolder()) {
                $folder = $object->getFolder();
                $messages = PageLayout::getMessages();
                delete_folder($folder->getId(), true);
                PageLayout::clearMessages();
                foreach ($messages as $message) {
                    PageLayout::postMessage($message);
                }
            }
        }
    }

    public function currentValue($object, $field, $sync)
    {
        return $object->hasFolder() ? 1 : 0;
    }
}
