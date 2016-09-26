<?php

class FleximportProcess extends SimpleORMap {

    static protected function configure($config = array())
    {
        $config['db_table'] = 'fleximport_processes';
        $config['has_many']['tables'] = array(
            'class_name' => 'FleximportTable',
            'on_delete' => 'delete',
            'on_store' => 'store'
        );
        parent::configure($config);
    }

}