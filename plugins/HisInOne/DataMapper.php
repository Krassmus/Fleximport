<?php

namespace HisInOne;

class DataMapper
{
    static public function getData($data_array)
    {
        $fields = [];
        $data = [];
        $glue = "__";

        //get the structure of the data
        $structure = [];
        foreach ((array) $data_array as $number => $row) {
            foreach ((array) $row as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $value = self::getStructure($value);
                    foreach ($value as $k => $v) {
                        if (!is_array($structure[$key])) {
                            $structure[$key] = [];
                        }
                        $structure[$key][$k] = $v;
                    }
                } else {
                    if (!is_array($structure[$key])) {
                        $structure[$key] = $value || $structure[$key];
                    }
                }
            }
            if (self::structureComplete($structure)) {
                break;
            }
        }


        //get the fields
        foreach ($structure as $key => $value) {
            if (is_array($value)) {
                foreach (self::getFlattenedStructure($value) as $v) {
                    $fields[] = strtolower($key. $glue. $v);
                }
            } else {
                $fields[] = strtolower($key);
            }
        }

        //get the data
        foreach ((array) $data_array as $number => $row) {
            $d = [];
            foreach ($structure as $key => $value) {
                if (is_array($value)) {
                    foreach (self::getFlattenedData($row->$key, $value) as $key => $v) {
                        $d[] = $v;
                    }
                } elseif ($value === "Array") {
                    $d[] = "Array";
                } else {
                    $d[] = $row->$key;
                }
            }
            $data[] = $d;
        }

        return [$fields, $data];
    }

    static protected function structureComplete($structure)
    {
        foreach ($structure as $key => $value) {
            if (is_array($value)) {
                if (!self::structureComplete($value)) {
                    return false;
                }
            } else {
                if ($value === null || $value === false) {
                    return false;
                }
            }
        }
        return true;
    }

    static protected function getStructure($data)
    {
        $output = [];
        foreach ((array) $data as $key => $value) {
            if (is_object($value)) {
                $value = self::getStructure($value);
                foreach ($value as $k => $v) {
                    $output[$key][$k] = is_array($v) ? $v : true;
                }
            } elseif (is_array($value)) {
                $output[$key] = "Array";
            } else {
                $output[$key] = true;
            }
        }
        return $output;
    }

    static protected function getFlattenedData($data, $structure)
    {
        $data_array = [];
        foreach ($structure as $key => $value) {
            if (is_array($value)) {
                foreach (self::getFlattenedData($data->$key, $value) as $k => $v) {
                    $data_array[] = $v;
                }
            } elseif ($value === "Array") {
                $data_array[] = "Array";
            } else {
                $data_array[] = $data->$key;
            }
        }
        return $data_array;
    }

    static protected function getFlattenedStructure($data)
    {
        $glue = "__";
        $output = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = self::getFlattenedStructure($value);
                foreach ($value as $k => $v) {
                    $output[] = $key . $glue . $v;
                }
            } else {
                $output[] = $key;
            }
        }
        return $output;
    }
}
