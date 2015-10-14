<?php

require_once __DIR__."/classes/FleximportTable.php";
require_once __DIR__."/plugins/FleximportPlugin.abstract.php";
foreach (scandir(__DIR__."/plugins") as $plugin) {
    if (!in_array($plugin, array(".", "..", "FleximportPlugin.abstract.php"))) {
        require_once __DIR__."/plugins/".$plugin;
    }
}

//require_once dirname(__file__)."/ImportPlugin.class.php";
//require_once dirname(__file__)."/classes/NSI_VeranstaltungTable.class.php";

class Fleximport extends StudIPPlugin {

    static public function CSV2Array($content, $delim = ';', $encl = '"', $optional = 1) {
        if ($content[strlen($content)-1]!="\r" && $content[strlen($content)-1]!="\n")
            $content .= "\r\n";

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
        return self::CSV2Array(file_get_contents($file_path), $delim, $encl, $optional);
    }

    public function __construct() {
        parent::__construct();
        if ($GLOBALS['perm']->have_perm("root")) {
            $navigation = new AutoNavigation($this->getDisplayName(), PluginEngine::getURL($this, array(), 'import/overview'));
            Navigation::addItem('/start/courseimport', $navigation);
            Navigation::addItem('/courseimport', $navigation);

            $navigation = new AutoNavigation(_("Import"), PluginEngine::getURL($this, array(), 'import/overview'));
            Navigation::addItem('/courseimport/overview', $navigation);
        }
    }

    protected function getDisplayName() {
        return _("Veranstaltungsimport");
    }
}
