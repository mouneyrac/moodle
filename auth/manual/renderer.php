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
 * Renderer for auth_manual plugin
 *
 * @package auth
 * @subpackage manual
 * @copyright  2012 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/authlib.php');

/**
 * Standard HTML output renderer for auth_manual
 *
 * @copyright  2012 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_manual_renderer extends auth_plugin_renderer_base {

    /**
     * Display login form (same as login.php + param to trigger link action)
     *
     * @param string $username
     * @param array $additionalparams the "hidden" param to add to the auth login form
     * @param string $errormsg error
     * @return string html of the login form (almost identical to login.php)
     */
    public function loginform($username, $additionalparams, $errormsg) {
        global $CFG;

        // Form title div.
        $title = html_writer::tag('h4',
                get_string('existingaccount', 'auth'),
                array('class' => ''));

        // Description div.
        $description = get_string("loginusing") . html_writer::empty_tag('br') .
                '('.get_string("cookiesenabled").')' . $this->help_icon('cookiesenabled');
        $description = html_writer::tag('div', $description, array('class' => 'desc'));

        // Errors div.
        $errors = '';
        if (!empty($errormsg)) {
            $errors = html_writer::tag('div', $this->error_text($errormsg), array('class' => 'loginerrors'));
        }

        // Username label.
        $usernamelabel = html_writer::label(get_string("username"), "username");
        $usernamelabeldiv = html_writer::tag('div', $usernamelabel, array('class' => 'form-label'));

        // Username input.
        $usernameinput = html_writer::empty_tag('input', array('type' => 'text',
            "name"=>"username", "id"=>"username", "size"=>"15", "value"=>s($username)));
        $usernameinputdiv = html_writer::tag('div', $usernameinput, array('class' => 'form-input'));

        // Generic Clearer div.
        $clearerdiv = html_writer::tag('div', '', array('class' => 'clearer'));

        // Password label.
        $passwordlabel = html_writer::label(get_string("password"), "password");
        $passwordlabeldiv = html_writer::tag('div', $passwordlabel, array('class' => 'form-label'));

        // Build hidden params.
        $additionalparamsinput = '';
        foreach ($additionalparams as $name => $value) {
            $additionalparamsinput .= html_writer::empty_tag('input',
                    array('type' => 'hidden', 'name' => $name, 'value' => $value));
        }

        // Password inputs.
        $passwordinputparams = array('type' => 'password',
            "name"=>"password", "id"=>"password", "size"=>"15", "value"=>"");
        if (!empty($CFG->loginpasswordautocomplete)) {
            $passwordinputparams['autocomplete'] = "off";
        }
        $passwordinput = html_writer::empty_tag('input', $passwordinputparams);
        $passwordsubmitparams = array("type" => "submit", "id" => "loginbtn", "value" => get_string("login"));
        $passwordsubmit = html_writer::empty_tag('input', $passwordsubmitparams);
        $passwordinputdiv = html_writer::tag('div', $additionalparamsinput . $passwordinput . $passwordsubmit,
                array('class' => 'form-input'));

        // Form content div.
        $formcontentdiv = html_writer::tag('div',
                $usernamelabeldiv . $usernameinputdiv . $clearerdiv . $passwordinputdiv , array('class' => 'loginform'));

        // Remember password div.
        $rememberpassdiv = '';
        if (isset($CFG->rememberusername) and $CFG->rememberusername == 2) {
            $rememberpassbox = html_writer::checkbox("rememberusername", "1", !empty($username));
            $rememberpasslabel = html_writer::label(get_string('rememberusername', 'admin'), "rememberusername");
            $rememberpassdiv = html_writer::tag('div', $rememberpassbox . $rememberpasslabel, array('class' => 'rememberpass'));
        }

        // Forgot password div.
        $forgotpasslink = html_writer::link(new moodle_url("forgot_password.php"), get_string("forgotten"));
        $forgotpassdiv = html_writer::tag('div', $forgotpasslink, array('class' => 'forgetpass'));

        // Main Form tag.
        $formparams = array('action' => $CFG->httpswwwroot . "/login/index.php", 'method' => 'post', 'id' => 'login');
        if (!empty($CFG->loginpasswordautocomplete)) {
            $formparams['autocomplete'] = "off";
        }
        $loginform = html_writer::tag('form',
                $formcontentdiv . $clearerdiv . $rememberpassdiv . $clearerdiv . $forgotpassdiv, $formparams);

        // The all login box with description / error /form.
        $entireloginbox = html_writer::tag('div', $description . $errors . $loginform, array('class' => 'subcontent loginsub'));

        // The title + login box.
        $alreadyregistereddiv = html_writer::tag('div', $title . $entireloginbox,
                  array('class' => 'alreadyregistered'));

        return $alreadyregistereddiv;
    }
}