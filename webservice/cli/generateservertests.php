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
 * Generate web service servers PHPunit tests
 *
 * @package    core_webservice
 * @category   external
 * @copyright  2013 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.5
 */
define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(__FILE__))).'/config.php');

class core_webservice_server_tests_generator {

    var $methodname;
    var $wsfctname;
    var $paramsdesc;
    var $classname;

    public function rest_client_code() {
        global $CFG;
        return '

        // Add a web service and token.
        $webservice = new stdClass();
        $webservice->name = \'Test web service2\';
        $webservice->enabled = true;
        $webservice->restrictedusers = false;
        $webservice->component = \'moodle\';
        $webservice->timecreated = time();
        $webservice->downloadfiles = true;
        $externalserviceid = $DB->insert_record(\'external_services\', $webservice);

        // Add a function to the service
        $DB->insert_record(\'external_services_functions\', array(\'externalserviceid\' => $externalserviceid,
            \'functionname\' => \''.$this->wsfctname.'\'));

        $externaltoken = new stdClass();
        $externaltoken->token = \'testtoken2\';
        $externaltoken->tokentype = 0;
        $externaltoken->userid = $USER->id;
        $externaltoken->externalserviceid = $externalserviceid;
        $externaltoken->contextid = 1;
        $externaltoken->creatorid = $USER->id;
        $externaltoken->timecreated = time();
        $DB->insert_record(\'external_tokens\', $externaltoken);

        //global $CFG;
        //require_once($CFG->dirroot . \'/webservice/cli/config.php\');
        $token = $externaltoken->token;
        $domainname = \''.$CFG->wwwroot.'\';
        $functionname = \''.$this->wsfctname.'\';
        $restformat = \'json\';
        //header(\'Content-Type: text/plain\');
        $serverurl = $domainname . \'/webservice/rest/server.php\'. \'?wstoken=\' . $token . \'&wsfunction=\'.$functionname;
        //require_once(\''.$CFG->dirroot.'/webservice/cli/curl.php\');
        $curl = new curl;
        $restformat = ($restformat == \'json\')?\'&moodlewsrestformat=\' . $restformat:\'\';
        $params = array(INSERT);
        $resp = $curl->post($serverurl . $restformat, $params);
        $REPLACE = json_decode($resp);
        ';
    }

    public function replacecall($data) {
        if (stristr($data, $this->classname . '::'.$this->methodname.'(')) {

            $clientcode = $this->rest_client_code() . "\n";

            return $clientcode . '//!!!!!!!!!!!!!!! ' . $data;
        }
        return $data;
    }

       public function generate_server_test_files() {
        global $DB, $CFG;

        // Delete all previously generated PHPunit test files.
        $mask = $CFG->dirroot . '/webservice/cli/ws_tmp_*_test.php';
        array_map( "unlink", glob( $mask ) );

        // Retrieve all ws functions from the DB.
        $functions = $DB->get_records('external_functions');
        $testedexternallibs = array();
        foreach ($functions as $function) {

        // Let's skip the deprecated function moodle starting with moodle_. We never wrote any PHPunit tests for them anyway.
        if (strpos($function->name, 'moodle_') !== 0) {

            // If component name is 'moodle' then take the first part of the ws function name to find the correct component.
            // I think there is a bug somewhere in Moodle, Moodle should have written the core component name (i.e. core_users, core_courses...).
            if ($function->component == 'moodle') {
                $namepart = explode('_', $function->classname);
                $function->component = $namepart[0] . '_' . $namepart[1];
            }

            $wstestpath = 'webservice/cli/ws_tmp_' . $function->component . '_test.php';
            $wstestfullpath = $CFG->dirroot . '/' . $wstestpath;
            //error_log(print_r($clientcode, true));
            // Create the ws_tmp_COMPONENT_test.php PHPunit test file if it doesn't exist yet.
            if (!array_key_exists($function->component, $testedexternallibs)) {
                $testfilepath = str_replace('externallib.php', 'tests/externallib_test.php', $function->classpath);
                $testfilefullpath = $CFG->dirroot  . '/' . $testfilepath;
                // For each externallib.php, detect if there is a /tests/externallib_test.php file.
                if (file_exists($testfilefullpath)) {
                    // There is a PHPunit test file for the externallib, so create one PHPunit for testing the web servers/services.
                    copy($testfilefullpath, $wstestfullpath);

                    // Run the PHPunit test.
                    $cmd = $CFG->dirroot . '/vendor/bin/phpunit ' . $wstestpath;
                    error_log(print_r($cmd, true));
                }
                $testedexternallibs[$function->component] = true;
            }

            if (file_exists($testfilefullpath)) {
                // Find all the lines where the external function is called and replace it by client code + ws call.
                $this->wsfctname = $function->name;
                $this->methodname = $function->methodname;
                $this->classname = $function->classname;
                error_log(print_r($this->wsfctname, true));
                $data = file($wstestfullpath); // Reads an array of lines.
                $data = array_map('self::replacecall', $data);
                file_put_contents($wstestfullpath, implode('', $data));
            }
        }
        }
    }
}

// Run the script.
$generator = new core_webservice_server_tests_generator();
$generator->generate_server_test_files();
