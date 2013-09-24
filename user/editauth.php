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
 * Multiple authentication settings page.
 *
 * @package    core_user
 * @copyright  2013 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../config.php");

//HTTPS is required in this page when $CFG->loginhttps enabled
$PAGE->https_required();

require_login();

$userid = optional_param('id', $USER->id, PARAM_INT);    // user id

$systemcontext = context_system::instance();

$PAGE->set_url('/user/editauth.php', array('id'=>$userid));
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('editmyauthentication', 'auth'));
$PAGE->set_heading($SITE->fullname);

require_login();


// Guest can not see auth methods.
if (isguestuser($userid)) {
    print_error('guestnoeditprofile');
}

// The user we are editing auth methods.
if (!$user = $DB->get_record('user', array('id'=>$userid))) {
    print_error('invaliduserid');
}

// Remote users cannot be edited.
if (is_mnet_remote_user($user)) {
    if (user_not_fully_set_up($user)) {
        $hostwwwroot = $DB->get_field('mnet_host', 'wwwroot', array('id'=>$user->mnethostid));
        print_error('usernotfullysetup', 'mnet', '', $hostwwwroot);
    }
    redirect($CFG->wwwroot . "/user/view.php?course={$course->id}");
}

// Load the primary auth plugin.
$primaryauth = get_auth_plugin($user->auth);

// Load the secondary auth plugins.
$secondaryauths = array();


echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('editmyauthentication', 'auth'));

// Make sure we really are on the https page when https login required.
$PAGE->verify_https_required();

// Print the HTML.
$renderer = $PAGE->get_renderer('core', 'user');
echo $renderer->user_auth($primaryauth, $secondaryauths);

echo $OUTPUT->footer();