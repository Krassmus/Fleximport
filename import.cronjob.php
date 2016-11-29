<?php

class FleximportJob extends CronJob
{
    /**
     * Returns the name of the cronjob.
     */
    public static function getName()
    {
        return _('Fleximport');
    }

    /**
     * Returns the description of the cronjob.
     */
    public static function getDescription()
    {
        return _('Führt einmal den Fleximport aus. Dazu werden alle eingestellten Tabellen nacheinander importiert. Bei externen Datenquellen werden vorher natürlich die Daten gezogen.');
    }

    /**
     * Setup method. Loads neccessary classes and checks environment. Will
     * bail out with an exception if environment does not match requirements.
     */
    public function setUp()
    {
        ini_set("memory_limit","1024M"); //won't work with suhosin
        $GLOBALS['FLEXIMPORT_IS_CRONJOB'] = true;
        require_once dirname(__file__)."/Fleximport.class.php";
    }

    /**
     * Return the parameters for this cronjob.
     *
     * @return Array Parameters.
     */
    public static function getParameters()
    {
        return array();
    }

    /**
     * Executes the cronjob.
     *
     * @param mixed $last_result What the last execution of this cronjob
     *                           returned.
     * @param Array $parameters Parameters for this cronjob instance which
     *                          were defined during scheduling.
     *                          Only valid parameter at the moment is
     *                          "verbose" which toggles verbose output while
     *                          purging the cache.
     */
    public function execute($last_result, $parameters = array())
    {
        $plugin = new Fleximport();
        $plugin->triggerImport();
    }
}
