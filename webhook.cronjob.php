<?php

class FleximportWebhookJob extends CronJob
{
    /**
     * Returns the name of the cronjob.
     */
    public static function getName()
    {
        return _('Fleximport (Webhooks)');
    }

    /**
     * Returns the description of the cronjob.
     */
    public static function getDescription()
    {
        return _('Sendet alle eingetragenen Webhooks, falls es Änderungen an den Tabellen gegeben hat.');
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
        $tables = FleximportTable::findAll();
        foreach ($tables as $table) {
            if (trim($table['webhook_urls'])) {
                $hash = $table->calculateChangeHash();
                if (!$table['change_hash'] || ($table['change_hash'] !== $hash)) {
                    $urls = (array) preg_split(
                        "/\s+/",
                        $table['webhook_urls'],
                        null,
                        PREG_SPLIT_NO_EMPTY
                    );
                    $payload = array(
                        'message' => "A table has new or changed data.",
                        'table' => $table['name'],
                        'server' => $GLOBALS['UNI_NAME_CLEAN'],
                        'server_id' => $GLOBALS['STUDIP_INSTALLATION_ID'],
                        'server_url' => $GLOBALS['ABSOLUTE_URI_STUDIP']
                    );
                    $payload = json_encode($payload);
                    foreach ($urls as $url) {
                        //Send webhook now:
                        $header = array();

                        /*if ($follower['security_token']) {
                            $calculatedHash = hash_hmac("sha1", $payload, $follower['security_token']);
                            $header[] = "X_HUB_SIGNATURE: sha1=".$calculatedHash;
                        }*/
                        $header[] = "Content-Type: application/json";

                        $r = curl_init();
                        curl_setopt($r, CURLOPT_URL, $url);
                        curl_setopt($r, CURLOPT_POST, true);
                        curl_setopt($r, CURLOPT_HTTPHEADER, $header);

                        curl_setopt($r, CURLOPT_POSTFIELDS, $payload);

                        $result = curl_exec($r);
                        curl_close($r);
                    }
                    $table['change_hash'] = $hash;
                    $table->store();
                }
            }
        }
    }
}
