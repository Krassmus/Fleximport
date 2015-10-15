#!/usr/bin/php -q
<?php

require_once dirname(__FILE__) . '/../../../../cli/studip_cli_env.inc.php';

require_once 'lib/plugins/core/StudIPPlugin.class.php';
if (file_exists(dirname(__FILE__) . '/Fleximport.class.php')) {
    include_once dirname(__FILE__) . '/Fleximport.class.php';
} else {
    echo _("Fehler: Plugin zum Importieren ist nicht installiert oder am falschen Ort.");
    exit;
}

$GLOBALS['IS_CLI'] = true;

echo sprintf(_("Um %s fängt ein neuer Durchlauf des Importscripts an."), date("H:i:s")._(" Uhr am ").date("j.n.Y"))."\n";

$plugin = new Fleximport();
$plugin->triggerImport();

echo sprintf(_("Um %s hört der Durchlauf des Importscripts auf."), date("H:i:s")._(" Uhr am ").date("j.n.Y"))."\n";

