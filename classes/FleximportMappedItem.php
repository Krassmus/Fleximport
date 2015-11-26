<?php

class FleximportMappedItem extends SimpleORMap {

    static protected function configure($config = array())
    {
        $config['db_table'] = 'fleximport_mapped_items';
        parent::configure($config);
    }

    static public function findbyItemId($item_id, $import_type)
    {
        return self::findOneBySQL("item_id = ? AND import_type = ?", array($item_id, $import_type));
    }

}