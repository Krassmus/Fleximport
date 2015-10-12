#!/usr/bin/php -q
<?php
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// show_room_groups.php
//
// Copyright (C) 2006 André Noack <noack@data-quest.de>,
// Suchi & Berg GmbH <info@data-quest.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../../../../cli/studip_cli_env.inc.php';

require_once 'lib/plugins/core/StudIPPlugin.class.php';
if (file_exists(dirname(__FILE__) . '/NSI_Import.class.php')) {
    include_once dirname(__FILE__) . '/NSI_Import.class.php';
} else {
    echo _("Fehler: Plugin zum Importieren ist nicht installiert oder am falschen Ort.");
    exit;
}

$GLOBALS['IS_CLI'] = true;

echo sprintf(_("Um %s fängt ein neuer Durchlauf des Importscripts an."), date("H:i:s")._(" Uhr am ").date("j.n.Y"))."\n";

$plugin = new NSI_Import();
$plugin->show_action();

echo sprintf(_("Um %s hört der Durchlauf des Importscripts auf."), date("H:i:s")._(" Uhr am ").date("j.n.Y"))."\n";

