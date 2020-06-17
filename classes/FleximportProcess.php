<?php

class FleximportProcess extends SimpleORMap {

    static protected function configure($config = array())
    {
        $config['db_table'] = 'fleximport_processes';
        $config['has_many']['tables'] = array(
            'class_name' => 'FleximportTable',
            'order_by' => 'ORDER BY name ASC',
            'on_delete' => 'delete',
            'on_store' => 'store'
        );
        $config['has_many']['configs'] = array(
            'class_name' => 'FleximportProcessConfig',
            'order_by' => 'ORDER BY name ASC',
            'on_delete' => 'delete',
            'on_store' => 'store'
        );
        parent::configure($config);
    }

    public function getConfig($name)
    {
        foreach ($this->configs as $config) {
            if ($config['name'] === $name) {
                return $config['value'];
            }
        }
    }

    public function setConfig($name, $value)
    {
        if ($this->isNew()) {
            $this->store();
        }
        foreach ($this->configs as $config) {
            if ($config['name'] === $name) {
                $config['value'] = $value;
                return $config->store();
            }
        }
        $config = new FleximportProcessConfig();
        $config['process_id'] = $this->getId();
        $config['name'] = $name;
        $config['value'] = $value;
        return $config->store();
    }

}
