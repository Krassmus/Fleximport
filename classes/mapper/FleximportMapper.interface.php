<?php

interface FleximportMapper {

    public function getName();

    public function possibleFieldnames();

    public function map($settings, $rawdata, $alreadymappeddata);

}