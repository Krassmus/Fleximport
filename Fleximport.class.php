<?php

require_once __DIR__."/classes/FleximportTable.php";
require_once __DIR__."/classes/FleximportConfig.php";
require_once __DIR__."/classes/FleximportProcess.php";
require_once __DIR__."/classes/FleximportProcessConfig.php";
require_once __DIR__."/classes/FleximportForeignKey.php";
require_once __DIR__."/classes/FleximportMappedItem.php";
require_once __DIR__ . "/classes/FleximportPlugin.abstract.php";
require_once __DIR__ . "/classes/mapper/FleximportMapper.interface.php";
require_once __DIR__ . "/classes/checker/FleximportChecker.interface.php";
require_once __DIR__ . "/classes/dynamics/FleximportDynamic.interface.php";
foreach (scandir(__DIR__."/classes/checker") as $checker) {
    if ($checker[0] !== "." && substr($checker, -4) === ".php") {
        require_once __DIR__."/classes/checker/".$checker;
    }
}
foreach (scandir(__DIR__."/classes/mapper") as $mapper) {
    if ($mapper[0] !== "." && substr($mapper, -4) === ".php") {
        require_once __DIR__."/classes/mapper/".$mapper;
    }
}
foreach (scandir(__DIR__."/classes/dynamics") as $dynamic) {
    if ($dynamic[0] !== "." && substr($dynamic, -4) === ".php") {
        require_once __DIR__."/classes/dynamics/".$dynamic;
    }
}
StudipAutoloader::addAutoloadPath(__DIR__ . '/plugins');
require_once __DIR__ . "/classes/sorm/SeminarCycleDateWithoutImplications.php";

class Fleximport extends StudIPPlugin implements SystemPlugin {

    static public function getCSSFormClass()
    {
        return "default";
    }

    static public function CSV2Array($content, $delim = ';', $encl = '"', $optional = 1) {
        if (($content[strlen($content) - 1] != "\r") && ($content[strlen($content) - 1] != "\n")) {
            $content .= "\r\n";
        }

        $reg = '/(('.$encl.')'.($optional?'?(?(2)':'(').
            '[^'.$encl.']*'.$encl.'|[^'.$delim.'\r\n]*))('.$delim.
            '|[\r\n]+)/smi';

        preg_match_all($reg, $content, $treffer);
        $linecount = 0;

        for ($i = 0; $i < count($treffer[3]);$i++) {
            $liste[$linecount][] = str_replace($encl.$encl, $encl, trim($treffer[1][$i],$encl));
            if ($treffer[3][$i] != $delim) $linecount++;
        }
        return $liste;
    }

    static public function getCSVDataFromFile($file_path, $delim = ';', $encl = '"', $optional = 1) {
        $bom = pack('H*','EFBBBF'); //remove BOM if there is one
        return self::CSV2Array(
            preg_replace("/^$bom/", '', file_get_contents($file_path)),
            $delim,
            $encl,
            $optional
        );
    }

    public function __construct() {
        parent::__construct();
        if ($GLOBALS['perm']->have_perm("root")) {
            $processes = FleximportProcess::findBySQL("1=1 ORDER BY name ASC");
            $navigation = new Navigation($this->getDisplayName());
            $navigation->setURL(PluginEngine::getURL($this, array(), 'import/overview'.(count($processes) ? "/".$processes[0]['process_id'] : "")));
            Navigation::addItem('/start/fleximport', $navigation);
            Navigation::addItem('/fleximport', $navigation);

            if (count($processes)) {
                foreach ($processes as $process) {
                    $navigation = new Navigation($process['name'], PluginEngine::getURL($this, array(), 'import/overview/'.$process->getId()));
                    Navigation::addItem('/fleximport/process_'.$process->getId(), $navigation);
                }
            } else {
                $navigation = new Navigation(_("Import"), PluginEngine::getURL($this, array(), 'import/overview'));
                Navigation::addItem('/fleximport/overview', $navigation);
            }
            $navigation = new Navigation(_("Konfiguration"), PluginEngine::getURL($this, array(), 'config/overview'));
            Navigation::addItem('/fleximport/config', $navigation);

            if (FleximportConfig::get("DISPLAY_AT_HEADER")) {
                if (is_numeric(FleximportConfig::get("DISPLAY_AT_HEADER"))) {
                    Navigation::getItem('/fleximport')->setImage(
                        Icon::create("install", "navigation")
                    );
                } else {
                    Navigation::getItem('/fleximport')->setImage(
                        Icon::create(FleximportConfig::get("DISPLAY_AT_HEADER"), "navigation")
                    );
                }
            }
        }
    }

    protected function getDisplayName() {
        return FleximportConfig::get("FLEXIMPORT_NAME") ?: _("Fleximport");
    }

    public function triggerImport($charge = "")
    {
        $processes = FleximportProcess::findBySQL("charge = ? AND triggered_by_cronjob = '1' ORDER BY name ASC", [$charge]);
        if ($GLOBALS['FLEXIMPORT_IS_CRONJOB']) {
            echo "Starting Import at ".date("c")."\n\n";
        }
        foreach ($processes as $process) {
            foreach ($process->tables as $table) {
                //import data if needed
                $table->fetchData();
            }
        }
        foreach ($processes as $process) {
            foreach ($process->tables as $table) {
                //possibly clean up data to save time
                $table->afterDataFetching();
            }
        }
        if ($GLOBALS['FLEXIMPORT_IS_CRONJOB']) {
            echo "Fetching data finished at ".date("c")."\n";
        }
        $protocol = array();
        foreach ($processes as $process) {
            foreach ($process->tables as $table) {
                if ($GLOBALS['FLEXIMPORT_IS_CRONJOB']) {
                    echo "\nStarting Import of ".$table['name']." at ".date("c")."\n";
                }
                $protocol = array_merge($protocol, $table->doImport());
            }
        }
        if (count($protocol) && $GLOBALS['FLEXIMPORT_IS_CRONJOB'] && FleximportConfig::get("REPORT_CRONJOB_ERRORS")) {
            $message = _("Es hat folgende Probleme beim Import gegeben:");

            $message .= "\n".implode("\n", $protocol);
            $mail = new StudipMail();
            $mail->setSubject(_("Fleximport Fehlerbericht von Stud.IP"));
            $mail->setBodyText($message);
            $emails = preg_split("/\s*[,;\s]+\s*/", FleximportConfig::get("REPORT_CRONJOB_ERRORS"), null, PREG_SPLIT_NO_EMPTY);
            foreach ($emails as $email) {
                $mail->addRecipient($email);
            }
            $mail->send();
        }
        if ($GLOBALS['FLEXIMPORT_IS_CRONJOB']) {
            echo implode("\n", $protocol);
        }

        if ($GLOBALS['FLEXIMPORT_IS_CRONJOB']) {
            echo "\nImport ends at ".date("c")."\n";
        }
    }
}
