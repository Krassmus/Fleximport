<?php

class FleximportProcess extends SimpleORMap {

    static protected function configure($config = array())
    {
        $config['db_table'] = 'fleximport_processes';
        parent::configure($config);
    }

}