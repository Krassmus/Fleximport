<?php

class FleximportModulteilFachsemesterMapper implements FleximportMapper
{

    public function getName()
    {
        return 'Fachsemester';
    }

    public function possibleFieldnames()
    {
        return [
            'fachsemester',
            'abschnitt_id'
        ];
    }

    public function possibleFormats()
    {
        $formats = [
            'fachsemester' => 'Empfohlenes Fachsemester',
            'abschnitt_id' => 'ID des Studiengangteil-Abschnittes'
        ];
        return $formats;
    }

    public function map($format, $value, $data, $sormclass)
    {
        $examreg_table =
            FleximportTable::findOneBySQL('name = ?',
                    ['fleximport_hisinone_g_examinationregulations']);
        switch ($format) {
            case 'fachsemester' :
                $line = $this->getLineById($examreg_table, $value);
                return $line['unitattributes__recommendedsemester'] ?: -1;
            case 'abschnitt_id' :
                do {
                    $line = $this->getLineById($examreg_table, $value);
                    $value = $line['parent_id'];
                } while ($line['parent_elementtype__key'] && $line['elementtype__key'] !== 'K');
                if ($line['id']) {
                    $fk = FleximportForeignKey::findOneBySQL(
                        "`sormclass` = 'StgteilAbschnitt' AND `foreign_key` = ?", [$line['id']]);
                    return $fk->item_id;
                }
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
