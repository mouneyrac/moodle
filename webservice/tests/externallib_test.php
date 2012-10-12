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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/externallib.php');
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * External course functions unit tests
 *
 * @package    core_webservice
 * @category   external
 * @copyright  2012 Paul Charsley
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_webservice_external_testcase extends externallib_advanced_testcase {

    public function setUp() {
        // Calling parent is good, always
        parent::setUp();

        // We always need enabled WS for this testcase
        set_config('enablewebservices', '1');
    }

    public function test_get_site_info() {
        global $DB, $USER, $CFG;

        $this->resetAfterTest(true);

        // This is the info we are going to check
        set_config('release', '2.4dev (Build: 20120823)');
        set_config('version', '2012083100.00');

        // Set current user
        $user = array();
        $user['username'] = 'johnd';
        $user['firstname'] = 'John';
        $user['lastname'] = 'Doe';
        self::setUser(self::getDataGenerator()->create_user($user));

        // Add a web service and token.
        $webservice = new stdClass();
        $webservice->name = 'Test web service';
        $webservice->enabled = true;
        $webservice->restrictedusers = false;
        $webservice->component = 'moodle';
        $webservice->timecreated = time();
        $webservice->downloadfiles = true;
        $externalserviceid = $DB->insert_record('external_services', $webservice);

        $_POST['wstoken'] = 'testtoken';
        $externaltoken = new stdClass();
        $externaltoken->token = 'testtoken';
        $externaltoken->tokentype = 0;
        $externaltoken->userid = $USER->id;
        $externaltoken->externalserviceid = $externalserviceid;
        $externaltoken->contextid = 1;
        $externaltoken->creatorid = $USER->id;
        $externaltoken->timecreated = time();
        $DB->insert_record('external_tokens', $externaltoken);

        $siteinfo = core_webservice_external::get_site_info();

        $this->assertEquals('johnd', $siteinfo['username']);
        $this->assertEquals('John', $siteinfo['firstname']);
        $this->assertEquals('Doe', $siteinfo['lastname']);
        $this->assertEquals($USER->id, $siteinfo['userid']);
        $this->assertEquals(true, $siteinfo['downloadfiles']);
        $this->assertEquals($CFG->release, $siteinfo['release']);
        $this->assertEquals($CFG->version, $siteinfo['version']);
    }

    /**
     * Test get_string
     */
    public function test_get_string() {
        $this->resetAfterTest(true);

        $service = new stdClass();
        $service->name = 'Dummy Service';
        $service->id = 12;

        $returnedstring = core_webservice_external::get_string('addservice', 'webservice',
                array(array('name' => 'name', 'value' => $service->name),
                      array('name' => 'id', 'value' => $service->id)));
        $corestring = get_string('addservice', 'webservice', $service);

        $this->assertEquals($corestring, $returnedstring);
    }

    /**
     * Test get_strings
     */
    public function test_get_strings() {
        $this->resetAfterTest(true);

        $service = new stdClass();
        $service->name = 'Dummy Service';
        $service->id = 12;

        $returnedstrings = core_webservice_external::get_strings(
                array(
                    array(
                        'stringid' => 'addservice', 'component' => 'webservice',
                        'stringparams' => array(array('name' => 'name', 'value' => $service->name),
                              array('name' => 'id', 'value' => $service->id)
                        )
                    ),
                    array('stringid' =>  'addaservice', 'component' => 'webservice')
                ));

        foreach($returnedstrings as $returnedstring) {
            $corestring = get_string($returnedstring['stringid'], $returnedstring['component'], $service);
            $this->assertEquals($corestring, $returnedstring['string']);
        }
    }

    /**
     * Test get_component_strings
     */
    public function test_get_component_strings() {
        global $USER;
        $this->resetAfterTest(true);

        $stringmanager = get_string_manager();

        $wsstrings = $stringmanager->load_component_strings('webservice', current_language());

        $componentstrings = core_webservice_external::get_component_strings('webservice');
        $this->assertEquals(count($componentstrings), count($wsstrings));
        foreach($wsstrings as $name => $string) {
            $this->assertEquals($string, $componentstrings[$name]);
        }
    }
}
