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
 * External files functions unit tests
 *
 * @package    core
 * @category   phpunit
 * @copyright  2012 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Files external PHPunit tests
 *
 * @package    core
 * @category   phpunit
 * @copyright  2012 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.3
 */
class core_files_external_testcase extends advanced_testcase {

    /**
     * Tests set up
     */
    protected function setUp() {
        global $CFG;
        require_once($CFG->dirroot . '/files/externallib.php');
    }

    /**
     * Test get_files
     */
    public function test_get_files() {
        global $USER;

        $this->setAdminUser();

        $this->resetAfterTest(true);

        $contextid = context_user::instance($USER->id)->id;
        $component = 'user';
        $filearea = 'private';
        $itemid = 0;
        $filepath = '/';
        $filename = null;

        // Call the external function.
        $files = core_files_external::get_files($contextid, $component, $filearea, $itemid, $filepath, $filename);

        //TODO: some checks. However I have some doubt about what to check. When ask info about private area
        //      I receive information about backup filearea. Is it normal? Behaviors of this
        //      external function need to be defined: MDL-33647
    }
}