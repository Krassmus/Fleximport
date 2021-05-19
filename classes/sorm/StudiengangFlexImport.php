<?php
/**
 * StudiengangFlexImport.php
 * Model class for Studiengang (table studiengang) without validation
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Peter Thienel <thienel@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

class StudiengangFlexImport extends Studiengang
{
    public function validate() {
        return null;
    }
}