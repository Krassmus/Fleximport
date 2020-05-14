<?php

class FleximportForeignKey extends SimpleORMap {

    static protected function configure($config = array())
    {
        $config['db_table'] = 'fleximport_foreign_keys';
        parent::configure($config);
    }

}
