<?php

/**
 * Interface FleximportPlugin
 * Alle Dateien in diesem Verzeichnis werden automatisch eingebunden. Sie sind gewissermaßen Subplugins zu
 * dem Fleximport-Plugin und ermöglichen besondere lokale Spezialitäten, die wir durch die Einstellmöglichkeiten
 * über die GUI des Plugins nicht ermöglichen könnten. Der Klassiker ist da sicher das dynamische Anlegen und
 * Einhängen in den Studienbereichsbaum. Aber es wäre zum Beispiel auch möglich, DoIt Aufgaben gleich mit über
 * die Veranstaltungstabelle anzulegen oder die Veranstaltungsbeschreibung über ein Template zu ermöglichen.
 */

abstract class FleximportPlugin
{

    protected $table;           //an instance of FleximportTable
    public $api = array();      //just some variable to store data of needed

    /**
     * The constructor. Usually you don't need to care about this.
     * @param FleximportTable $table
     */
    public function __construct(FleximportTable $table)
    {
        $this->table = $table;
    }

    /**
     * Returns an array of names of config-parameters.
     * @return array of strings
     */
    public function neededConfigs()
    {
        return array();
    }

    /**
     * Returns an array of names of config-parameters that are relevant for this process.
     * @return array of strings
     */
    public function neededProcessConfigs()
    {
        return array();
    }

    /**
     * Returns whether this plugins handles the import
     * @return bool
     */
    public function customImportEnabled()
    {
        return false;
    }

    /**
     * You can specify a custom data-source.
     * @return bool
     */
    public function fetchData()
    {
    }

    /**
     * Executed directly after the
     */
    public function afterDataFetching()
    {
    }

    /**
     * Transforms the body of the webhook push data to an array of associative arrays
     * @param string $body: the body of the push data. Can be json or csv or xml.
     * @param string $mime_type: the content-type of the request. Might be indicating what data the body contains.
     * @return string|array of arrays: each of the returned arrays must be an associative array that fits to the table
     *      and its mapping like array('user_id' => "1456464", 'email' => "fuhse@data-quest.de"). If you don't want to
     *      use that method simply return the body variable.
     */
    function fetchPushData($body, $mime_type = null)
    {
        return $body;
    }

    /**
     * Indicates which fields will be mapped. Returns an array of string fieldnames. These are the
     * fieldnames of the target table like seminare - not of the source-table, where you get your data from.
     * Note that this list can also contain some dynamic fielnames like fleximport_dozenten .
     * @return array of fieldnames
     */
    public function fieldsToBeMapped()
    {
        return array();
    }

    /**
     * @param string $field: name of the field of target table (not the imported table!) like
     * @param array line: the dataline of the table
     * @return mixed: if no mapping should apply map to false. null maps
     * to database NULL. Any other value will map to a string value.
     */
    public function mapField($field, $line)
    {
        return false; //means there is no mapping in this plugin.
    }

    /**
     * Checks a line of the table and returns any errors as a string.
     * @param array $line : dataline in the table.
     * @return string : a description of the problem  or the empty string
     *                  if no problem is detected.
     */
    public function checkLine($line)
    {
        return "";
    }

    /**
     * Just a callback do do some additional work before the plain import happens.
     * @param SimpleORMap $object : the not yet stored object.
     * @param array $line : the current dataline that is going to be processed
     * @param array $mappeddata : the mapped values
     */
    public function beforeUpdate(SimpleORMap $object, $line, $mappeddata)
    {
    }

    /**
     * Just a callback do do some additional work after the plain import happened.
     * @param SimpleORMap $object : the new and already stored object.
     * @param array $line : the current dataline that was processed
     */
    public function afterUpdate(SimpleORMap $object, $line)
    {
    }

    /**
     * Returns a description of what this plugin is doing.
     * @return null\string
     */
    public function getDescription()
    {
        return null;
    }
}
