<?php

class FleximportStgteilAbschnittConcatenatedLabelMapper implements FleximportMapper
{

    public function getName()
    {
        return 'name';
    }

    public function possibleFieldnames()
    {
        return array('name');
    }

    public function possibleFormats()
    {
        $formats = array(
            'name' => 'Zusammengesetzte Konto-Namen'
        );
        return $formats;
    }

    public function map($format, $value, $data, $sormclass)
    {
        $depth = FleximportConfig::get('HISINONE_ABSCHNITT_LABELS_DEPTH') ?: 1;
        $labels = [];
        switch ($format) {
            case "name":
                $examreg_table =
                    FleximportTable::findOneBySQL('name = ?',
                            ['fleximport_hisinone_g_examinationregulations']);
                do {
                    $line = $this->getLineById($examreg_table, $value);
                    $labels[] = $line['defaulttext'];
                    $value = $line['parent_id'];
                } while (count($labels) < $depth && $line['elementtype__key'] === 'K');
        }
        return implode(' > ', array_reverse($labels));
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
