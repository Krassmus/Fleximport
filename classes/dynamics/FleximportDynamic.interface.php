<?php

abstract class FleximportDynamic
{

    /**
     * Returns an associative array. Its indexes are the Classnames of SORM-classes and its values are arrays
     * with the field-names. All of these field-names are no fields of the SORM-class but will be treated
     * as additional fields that can be mapped.
     * array('ClassName' => array("Fieldname", "Fieldname2"))
     * @return array
     */
    abstract public function forClassFields();

    /**
     * Return true if this field needs an array of multiple values or only one (false).
     * @return boolean
     */
    abstract public function isMultiple();

    /**
     * @param SimpleORMap $object : the object that this Dynamic applies to
     * @param mixed $value : an array or just one value.
     */
    public function applyValue($object, $value, $line, $sync)
    {

    }

    public function applyValueBeforeStore($object, $value, $line, $sync)
    {

    }

    /**
     * You can use this method to display the current values of an existing object
     */
    abstract public function currentValue($object, $field, $sync);

}
