<?php

// This file is NOT part of Moodle - http://moodle.org/
//
// This is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This software is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * OAuth credentials admin UI
 *
 * @package    webservice_oauth
 * @copyright  2011 MuchLearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/webservice/forms.php');
require_once($CFG->libdir . '/externallib.php');

$action = required_param('action', PARAM_ACTION);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

if ($action == 'viewsecret') {
    $PAGE->https_required();
}

admin_externalpage_setup('addoauthcredentials');

//Deactivate the second 'Manage credentials' navigation node, and use the main 'Manage credentials' navigation node
$node = $PAGE->settingsnav->find('addoauthcredentials', navigation_node::TYPE_SETTING);
$newnode = $PAGE->settingsnav->find('oauthcredentials', navigation_node::TYPE_SETTING);
if ($node && $newnode) {
    $node->display = false;
    $newnode->make_active();
}

require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

$credentialslisturl = new moodle_url("/" . $CFG->admin . "/settings.php", array('section' => 'oauthcredentials'));

require_once($CFG->dirroot . "/webservice/lib.php");
$webservicemanager = new webservice();

switch ($action) {

    case 'create':
        $mform = new oauth_credentials_form(null, array('action' => 'create'));
        $data = $mform->get_data();
        if ($mform->is_cancelled()) {
            redirect($credentialslisturl);
        } else if ($data and confirm_sesskey()) {
            ignore_user_abort(true);

            //check the the user is allowed for the service
            $selectedservice = $webservicemanager->get_external_service_by_id($data->service);
            if ($selectedservice->restrictedusers) {
                $restricteduser = $webservicemanager->get_ws_authorised_user($data->service, $data->user);
                if (empty($restricteduser)) {
                    $allowuserurl = new moodle_url('/' . $CFG->admin . '/webservice/service_users.php',
                                                   array('id' => $selectedservice->id));
                    $allowuserlink = html_writer::tag('a', $selectedservice->name , array('href' => $allowuserurl));
                    $errormsg = $OUTPUT->notification(get_string('usernotallowed', 'webservice', $allowuserlink));
                }
            }

            //process the creation
            if (empty($errormsg)) {
                //TODO improvement: either move this function from externallib.php to webservice/lib.php
                // either move most of webservicelib.php functions into externallib.php
                // (create externalmanager class) MDL-23523
                oauth_generate_credentials($data->service, $data->user,
                        get_context_instance(CONTEXT_SYSTEM), $data->signmethod,
                        null, $data->validuntil, $data->iprestriction);
                redirect($credentialslisturl);
            }
        }

        //OUTPUT: create credentials form
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('createoauthcredentials', 'webservice'));
        if (!empty($errormsg)) {
            echo $errormsg;
        }
        $mform->display();
        echo $OUTPUT->footer();
        die;

    case 'delete':
        $credentialsid = required_param('credentialsid', PARAM_INT);
        $credentials = $webservicemanager->get_created_by_user_oauth_credentials($USER->id, $credentialsid);

        //Delete the credentials
        if ($confirm and confirm_sesskey()) {
            $webservicemanager->delete_user_oauth_credentials($credentials->id);
            redirect($credentialslisturl);
        }

        ////OUTPUT: display delete credentials confirmation box
        echo $OUTPUT->header();
        $renderer = $PAGE->get_renderer('core', 'webservice');
        echo $renderer->admin_delete_oauth_credentials_confirmation($credentials);
        echo $OUTPUT->footer();
        die;

    case 'viewsecret':
        $credentialsid = required_param('credentialsid', PARAM_INT);
        $credentials = $webservicemanager->get_created_by_user_oauth_credentials($USER->id, $credentialsid);
        echo $OUTPUT->header();
        print_string('oauthsecretdisplay', 'webservice', $credentials);
        echo html_writer::tag('p', html_writer::link($credentialslisturl, get_string('back')));
        echo $OUTPUT->footer();
        die;

    default:
        //wrong url access
        redirect($credentialslisturl);
        break;
}
