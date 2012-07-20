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
 * Airnotifier message processor to send messages to the APNS provider: airnotfier. 
 * (https://github.com/dongsheng/airnotifier)
 *
 * @package    message_airnotifier
 * @copyright  2012 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
require_once($CFG->dirroot . '/message/output/lib.php');
require_once($CFG->dirroot . '/message/output/airnotifier/lib.php');

/**
 * The airnotifier message processor
 *
 * @package   message_airnotifier
 * @copyright 2012 Jerome Mouneyrac
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class message_output_airnotifier extends message_output {

    /**
     * Processes the message and sends a notification via airnotifier
     *
     * @param stdClass $eventdata the event data submitted by the message sender plus $eventdata->savedmessageid
     * @return true if ok, false if error
     */
    function send_message($eventdata) {
        global $CFG;
        
        varlog($eventdata);

        if (!empty($CFG->noemailever)) {
            // hidden setting for development sites, set in config.php if needed
            debugging('$CFG->noemailever active, no airnotifier message sent.', DEBUG_MINIMAL);
            return true;
        }

        // skip any messaging suspended and deleted users
        if ($eventdata->userto->auth === 'nologin' or $eventdata->userto->suspended or
                $eventdata->userto->deleted) {
            return true;
        }

        // Building the notification
        $notification = fullname($eventdata->userfrom) . ': ' . $eventdata->smallmessage;
        if (!empty($eventdata->contexturl)) {
            $notification .= "\n" . get_string('view') . ': ' . $eventdata->contexturl;
        }

        // We are sending to message to all devices
        $airnotifiermanager = new airnotifier_manager();
        $devicetokens = $airnotifiermanager->get_user_devices($CFG->airnotifierappname, $eventdata->userto->id, true);

        foreach ($devicetokens as $devicetoken) {

            // Sending the message to the device
            $serverurl = $CFG->airnotifierurl . ':' . $CFG->airnotifierport . '/notification/';
            $header = array('Accept: application/json', 'X-AN-APP-NAME: ' . $CFG->airnotifierappname,
                'X-AN-APP-KEY: ' . $CFG->airnotifieraccesskey);
            $curl = new curl;
            $curl->setHeader($header);
            $params = array('alert' => $notification,
                'token' => $devicetoken->devicenotificationtoken);
            if (!empty($eventdata->url)) {
                $params['url'] = $eventdata->url;
            }
            $resp = $curl->post($serverurl, $params);
        }

        return true;
    }

    /**
     * Creates necessary fields in the messaging config form.
     *
     * @param array $preferences An array of user preferences
     */
    function config_form($preferences) {
        global $CFG, $OUTPUT, $USER, $PAGE;

        if (!$this->is_system_configured()) {
            return get_string('notconfigured', 'message_airnotifier');
        } else {

            $PAGE->requires->css('/message/output/airnotifier/style.css');

            $airnotifiermanager = new airnotifier_manager();
            $devicetokens = $airnotifiermanager->get_user_devices($CFG->airnotifierappname, $USER->id);

            if (!empty($devicetokens)) {
                $output = '';

                foreach ($devicetokens as $devicetoken) {

                    if ($devicetoken->enable) {
                        $hideshowiconname = 't/hide';
                        $dimmed = '';
                    } else {
                        $hideshowiconname = 't/show';
                        $dimmed = 'dimmed_text';
                    }

                    $hideshowicon = $OUTPUT->pix_icon($hideshowiconname, get_string('showhide', 'message_airnotifier'));
                    $deleteicon = $OUTPUT->pix_icon('t/delete', get_string('deletedevice', 'message_airnotifier'));
                    $devicename = empty($devicetoken->devicename) ? get_string('unknowndevice', 'message_airnotifier') : s($devicetoken->devicename);
                    $hideurl = new moodle_url('message/output/airnotifier/action.php',
                                    array('hide' => !$devicetoken->enable, 'deviceid' => $devicetoken->id,
                                        'sesskey' => sesskey()));
                    $deleteurl = new moodle_url('message/output/airnotifier/action.php',
                                    array('delete' => true, 'deviceid' => $devicetoken->id,
                                        'sesskey' => sesskey()));

                    $output .= html_writer::start_tag('li', array('id' => $devicetoken->id, 'class' => 'airnotifierdevice ' . $dimmed)) . "\n";
                    $output .= html_writer::label($devicename, 'deviceid-' . $devicetoken->id, array('class' => 'devicelabel ')) . ' ' .
                            html_writer::link($hideurl, $hideshowicon, array('class' => 'hidedevice', 'alt' => 'show/hide')) . '' .
                            html_writer::link($deleteurl, $deleteicon, array('class' => 'deletedevice', 'alt' => 'delete')) . "\n";
                    $output .= html_writer::end_tag('li') . "\n";
                }

                // Include the AJAX script to automatically trigger the action
                $airnotifiermanager->include_device_ajax();

                $output = html_writer::tag('ul', $output, array('id' => 'airnotifierdevices'));
            } else {
                $output = get_string('nodevices', 'message_airnotifier');
            }
            return $output;
        }
    }

    /**
     * Parses the submitted form data and saves it into preferences array.
     *
     * @param stdClass $form preferences form class
     * @param array $preferences preferences array
     */
    function process_form($form, &$preferences) {
        if (isset($form->airnotifier_devicetoken) && !empty($form->airnotifier_devicetoken)) {
            $preferences['message_processor_airnotifier_devicetoken'] = $form->airnotifier_devicetoken;
        }
    }

    /**
     * Loads the config data from database to put on the form during initial form display
     *
     * @param array $preferences preferences array
     * @param int $userid the user id
     */
    function load_data(&$preferences, $userid) {
        $preferences->airnotifier_devicetoken =
                get_user_preferences('message_processor_airnotifier_devicetoken', '', $userid);
    }

    /**
     * Tests whether the airnotifier settings have been configured
     * @return boolean true if airnotifier is configured
     */
    function is_system_configured() {
        global $CFG;
        return (!empty($CFG->airnotifierurl) && !empty($CFG->airnotifierport) &&
                !empty($CFG->airnotifieraccesskey) && !empty($CFG->airnotifierappname));
    }

    /**
     * Tests whether the airnotifier settings have been configured on user level
     * @param  object $user the user object, defaults to $USER.
     * @return bool has the user made all the necessary settings
     * in their profile to allow this plugin to be used.
     */
    function is_user_configured($user = null) {
        global $USER;

        if (is_null($user)) {
            $user = $USER;
        }
        return (bool) get_user_preferences('message_processor_airnotifier_devicetoken', null, $user->id);
    }

}

