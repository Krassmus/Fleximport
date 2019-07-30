<?php

class FleximportConfig {

    static protected $instance = null;

    protected $variables = null;

    static public function template($text, $data, $rawline, $singleinput = null)
    {
        if ($singleinput) {
            $text = str_ireplace("{{input}}", $singleinput, $text);
        }
        foreach ($data as $index => $value) {
            $text = str_ireplace("{{".$index."}}", $value, $text);
        }
        foreach ($rawline as $index => $value) {
            if (!in_array($index, $data)) {
                $text = str_ireplace("{{".$index."}}", $value, $text);
            }
        }
        $functions = array("md5", "urlencode", "strip_whitespace");
        foreach ($functions as $function) {
            $text = preg_replace_callback(
                "/".strtoupper($function)."\(([^\)]*)\)/",
                function ($match) use ($function) {
                    if ($function === "strip_whitespace") {
                        return preg_replace("/\s+/", "", $match[1]);
                    } else {
                        return $function($match[1]);
                    }
                },
                $text
            );
            //$template = preg_match_all("fghgjfjhgfhf", $function."(\"\\1\")", $template);
        }
        return $text;
    }

    /**
     * Returns all configs as an associative array.
     * @return array
     */
    static public function all()
    {
        if (!self::$instance) {
            self::$instance = new FleximportConfig();
        }
        self::$instance->fetchVariables();
        return self::$instance->variables;
    }

    static public function get($name = null)
    {
        if (!self::$instance) {
            self::$instance = new FleximportConfig();
        }
        if ($name !== null) {
            return self::$instance->$name;
        } else {
            return self::$instance;
        }
    }

    static public function set($name, $value)
    {
        if (!self::$instance) {
            self::$instance = new FleximportConfig();
        }
        self::$instance->$name = $value;
    }

    static public function delete($name)
    {
        if (!self::$instance) {
            self::$instance = new FleximportConfig();
        }
        self::$instance->$name = null;
    }

    public function __get($name)
    {
        if ($this->variables === null) {
            $this->fetchVariables();
        }
        return $this->variables[$name];
    }

    public function __set($name, $value)
    {
        if ($this->variables === null) {
            $this->fetchVariables();
        }
        $this->variables[$name] = $value;
        $this->store($name);
    }

    public function __isset($val)
    {
        if ($this->variables === null) {
            $this->fetchVariables();
        }
        return isset($this->variables[$val]);
    }

    protected function fetchVariables()
    {
        $statement = DBManager::get()->prepare("
            SELECT name, value
            FROM fleximport_configs
            ORDER BY name ASC
        ");
        $statement->execute();
        $this->variables = $statement->fetchPairs();
    }

    protected function store($name)
    {
        if ($this->variables[$name] === "" || $this->variables[$name]) {
            $statement = DBManager::get()->prepare("
                INSERT INTO fleximport_configs
                SET name = :name,
                    value = :value,
                    mkdate = UNIX_TIMESTAMP(),
                    chdate = UNIX_TIMESTAMP()
                ON DUPLICATE KEY UPDATE
                    value = :value,
                    chdate = UNIX_TIMESTAMP()
            ");
            return $statement->execute(array(
                'name' => $name,
                'value' => $this->variables[$name]
            ));
        } else {
            $statement = DBManager::get()->prepare("
                DELETE FROM fleximport_configs
                WHERE name = :name
            ");
            return $statement->execute(array(
                'name' => $name
            ));
        }
    }

}