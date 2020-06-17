<?php

class FleximportProcessConfig  extends SimpleORMap {

    static protected function configure($config = array())
    {
        $config['db_table'] = 'fleximport_process_configs';
        $config['belongs_to']['process'] = array(
            'class_name' => 'FleximportProcess',
            'foreign_key' => 'process_id'
        );
        parent::configure($config);
    }

}
