<?php

class FleximportCourseFolderDynamic extends FleximportDynamic
{

    public function forClassFields()
    {
        return array(
            'Course' => array("fleximport_course_folder" => _("Ordner"))
        );
    }

    public function isMultiple()
    {
        return true;
    }

    public function applyValue($object, $value, $line, $sync)
    {
        $newfolders = array();
        foreach ($value as $f) {
            $success = preg_match("/(.+)\s*\((.+)\)/", $f, $matches);
            $foldertype = trim($matches[2]);
            if ($success
                    && class_exists($foldertype)
                    && is_subclass_of($foldertype, "FolderType")
                    && $foldertype::availableInRange($object, $GLOBALS['user']->id)) {
                $newfolders[trim($matches[1])] = $foldertype;
            } else {
                $newfolders[$f] = "StandardFolder";
            }
        }
        $value = $newfolders;

        $topfolder = Folder::findTopFolder($object->getId());
        $folders = array();
        if ($topfolder) {
            foreach ($topfolder->subfolders as $subfolder) {
                $folders[] = $subfolder['name'];
            }
        }
        foreach (array_diff(array_keys($value), $folders) as $newfoldername) {
            FileManager::createSubFolder(
                $topfolder->getTypedFolder(),
                User::findCurrent(),
                $value[$newfoldername],
                $newfoldername
            );
        }
        foreach (array_intersect(array_keys($value), $folders) as $foldername) {
            $retypefolder = Folder::findOneBySQL("parent_id = ? AND name = ?", array(
                $topfolder->getId(),
                $foldername
            ));
            $foldertype = $value[$foldername];
            if ($retypefolder
                    && ($retypefolder['folder_type'] !== $foldertype)
                    && class_exists($foldertype)
                    && is_subclass_of($foldertype, "FolderType")
                    && $foldertype::availableInRange($object, $GLOBALS['user']->id)) {
                $retypefolder['folder_type'] = $foldertype;
                $retypefolder->store();
            }
        }
        if ($sync) {
            foreach (array_diff($folders, array_keys($value)) as $badfolder) {
                $badfolder = Folder::findOneBySQL("parent_id = ? AND name = ?", array(
                    $topfolder->getId(),
                    $badfolder
                ));
                if ($badfolder) {
                    $badfolder->delete();
                }
            }
        }
    }

    public function currentValue($object, $field, $sync)
    {
        $topfolder = Folder::findTopFolder($object->getId());

        $folders = array();
        if ($topfolder) {
            foreach ($topfolder->subfolders as $subfolder) {
                $folders[] = $subfolder['name']." (".$subfolder['folder_type'].")";
            }
        }
        return $folders;
    }
}
