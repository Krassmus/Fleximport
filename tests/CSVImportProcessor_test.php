<?php
/*
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Rasmus Fuhse <fuhse@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP-Plugins
 * @since       2.1
 */

require_once dirname(__file__)."/../classes/CSVImportProcessor.php";

class CSVImportProcessorTest extends UnitTestCase {

    function testCSV2Array() {
        $input =
'"erste Spalte";"zweite Spalte"
"hallo";"Du ""da"" hinten"';
        $output = CSVImportProcessor::CSV2Array($input);
        $this->assertIsA($output, "array");
        $this->assertEqual(count($output), 2);
        $this->assertIsA($output[0], "array");
        $this->assertIsA($output[1], "array");
        $this->assertEqual($output[0][1], 'zweite Spalte');
        $this->assertEqual($output[1][1], 'Du "da" hinten');
    }

    function testFileImport() {
        $filepath = dirname(__file__)."/test.csv";
        $var = CSVImportProcessor::getCSVDataFromFile($filepath);
        $this->assertIsA($var, "array");
        $this->assertEqual(count($var), 3);
        $this->assertEqual($var[1][2], "Eintrag 1 3");
    }

    function testReduceDiakritika() {
        $badstring = "äöüßffffeèèÐ gæøØ_;^ha";
        $this->assertEqual(CSVImportProcessor::reduce_diakritika_from_iso88591($badstring), "aeoeuessffffeeeD gaoO_;^ha");
    }

    
}