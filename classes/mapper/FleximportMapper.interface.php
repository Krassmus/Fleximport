<?php

interface FleximportMapper
{

    public function getName();

    public function possibleFieldnames();

    public function map($format, $value, $data, $sormclass);

}
