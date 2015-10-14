<?php

/**
 * Interface FleximportPlugin
 * Alle Dateien in diesem Verzeichnis werden automatisch eingebunden. Sie sind gewissermaßen Subplugins zu
 * dem Fleximport-Plugin und ermöglichen besondere lokale Spezialitäten, die wir durch die Einstellmöglichkeiten
 * über die GUI des Plugins nicht ermöglichen könnten. Der Klassiker ist da sicher das dynamische Anlegen und
 * Einhängen in den Studienbereichsbaum. Aber es wäre zum Beispiel auch möglich, DoIt Aufgaben gleich mit über
 * die Veranstaltungstabelle anzulegen oder die Veranstaltungsbeschreibung über ein Template zu ermöglichen.
 */

abstract class FleximportPlugin {

    protected $table; //an instance of FleximportTable

    /**
     * The constructor. Usually you don't need to care about this.
     * @param FleximportTable $table
     */
    public function __construct(FleximportTable $table) {
        $this->table = $table;
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
     * Just a callback do do some additional work after the plain import happened.
     * @param SimpleORMap $object : the new and already stored object.
     * @param array $line : the current dataline that was processed
     */
    public function afterUpdate(SimpleORMap $object, $line)
    {
    }
}