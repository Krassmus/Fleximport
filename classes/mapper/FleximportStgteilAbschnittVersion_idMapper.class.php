<?php

class FleximportStgteilAbschnittdVersion_idMapper implements FleximportMapper
{

    public function getName()
    {
        return 'version_id';
    }

    public function possibleFieldnames()
    {
        return array('version_id');
    }

    public function possibleFormats()
    {
        $formats = array(
            'version_id' => 'ID der Studiengangteil-Version von Konto'
        );
        return $formats;
    }

    public function map($format, $value, $data, $sormclass)
    {
        $po_id = '';
        switch ($format) {
            case 'version_id' :
                $examreg_table =
                    FleximportTable::findOneBySQL('name = ?',
                            ['fleximport_hisinone_g_examinationregulations']);
                do {
                    $line = $this->getLineById($examreg_table, $value);
                    $po_id = $line['lid'];
                    $value = $line['parent_id'];
                } while ($line['elementtype__key'] === 'K');
                $fk = FleximportForeignKey::findOneBySQL(
                        "`sormclass` = 'StgteilVersion' AND `foreign_key` = ?", [$po_id]);
                return $fk->item_id;
        }
        return '';
    }
    
    private function getLineById($table, $id)
    {
        $statement = DBManager::get()->prepare("
            SELECT *
            FROM `".addslashes($table->getDBName())."`
            WHERE `id` = :id
        ");
        $statement->execute(array('id' => $id));
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

}
