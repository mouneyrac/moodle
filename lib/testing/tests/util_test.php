<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Test util library
 * @package    core
 * @category   test
 * @copyright  2014 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__.'/../classes/util.php');

/**
 * Test util library
 *
 * @package    core
 * @category   test
 * @copyright  2014 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_test_util_testcase extends advanced_testcase {

    /**
     * @var mock_util the mock test framework class.
     */
    private $mockutil;

    /**
     * Init the mock test framework.
     */
    protected function setUp()
    {
        $this->mockutil = new mock_util();
    }

    /**
     * Test reset dataroot.
     */
    public function test_reset_dataroot() {

        $this->mockutil->installjsonfile();

        // Check that the json file has been created and contain the init data root filedir file.
        $this->assertEquals(true, file_exists($this->mockutil->get_dataroot() . '/' . $this->mockutil->get_originaldatafilesjson()));

        // Create a file in dataroot (Simulate creation of a file when running a test).
        $file = $this->mockutil->get_dataroot() . '/filedir/testfile.txt';
        $content = "a really fake test data file\n";
        $fp = fopen($file, "wb");
        fwrite($fp, $content);
        fclose($fp);

        // Check we have two files in the filedir dataroot.
        $fi = new FilesystemIterator($this->mockutil->get_dataroot() . '/filedir/', FilesystemIterator::SKIP_DOTS);
        $this->assertEquals(2, iterator_count($fi));

        $this->mockutil->reset_dataroot();

        // Check we have only one file in the dataroot.
        $fi = new FilesystemIterator($this->mockutil->get_dataroot() . '/filedir/', FilesystemIterator::SKIP_DOTS);
        $this->assertEquals(1, iterator_count($fi));

        // Check that the json file is still here.
        $this->assertEquals(true, file_exists($this->mockutil->get_dataroot() . '/' . $this->mockutil->get_originaldatafilesjson()));

        $this->mockutil->dropdataroot();

        // Check that there is no json file.
        $this->assertEquals(false, file_exists($this->mockutil->get_dataroot() . '/' . $this->mockutil->get_originaldatafilesjson()));

        // Check there is not filedir directory.
        $this->assertEquals(0, file_exists($this->mockutil->get_dataroot() . '/filedir'));
    }
}

/**
 * Class mock_util
 *
 * This is a mock class to simulate phpunit/behat framework.
 * We need this mock class so we don't conflict with phpunit dataroot or wreck behat dataroot.
 * @copyright  2014 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mock_util extends testing_util {

    /**
     * @var array Files to skip when resetting dataroot folder.
     */
    protected static $datarootskiponreset = array('.', '..', 'mocktestdir.txt', 'mock', '.htaccess');

    /**
     * @var array Files to skip when dropping dataroot folder.
     */
    protected static $datarootskipondrop = array('.', '..', 'lock', 'webrunner.xml');

    // We are using PHPunit dataroot to save our mock dataroot.
    protected $mockdataroot;

    /**
     * Constructor.
     */
    public function __construct() {
        global $CFG;

        $this->mockdataroot = $CFG->phpunit_dataroot . '/mockdataroot';

        // Create a new dataroot.
        mkdir($this->mockdataroot);
        // Create a new dataroot.
        mkdir($this->mockdataroot . '/filedir');
        // Create the framework folder.
        mkdir($this->mockdataroot . '/mock');

        self::set_dataroot($this->mockdataroot);

        // Create a file into filedir (simulate file created during site installation).
        $file = self::get_dataroot() . '/filedir/initfile.txt';
        $content = "a really fake data file\n";
        $fp = fopen($file, "wb");
        fwrite($fp, $content);
        fclose($fp);
    }

    /**
     * Call save_original_data_files().
     */
    public function installjsonfile() {
        self::save_original_data_files();
    }

    /**
     * Call drop_dataroot().
     */
    public function dropdataroot() {
        self::drop_dataroot();
    }
}
