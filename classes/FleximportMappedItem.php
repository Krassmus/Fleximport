<?php

class FleximportMappedItem extends SimpleORMap {

    static protected function configure($config = array())
    {
        $config['db_table'] = 'fleximport_mapped_items';
        parent::configure($config);
    }

    static public function findbyItemId($item_id, $table_id)
    {
        return self::findOneBySQL("item_id = ? AND table_id = ?", array($item_id, $table_id));
    }

}