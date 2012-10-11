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
 * The core auth renderer
 *
 * @package    core_auth
 * @copyright  2012 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/pluginlib.php');

/**
 * Standard HTML output renderer for auth
 *
 * @copyright  2012 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_auth_renderer extends plugin_renderer_base {

    /**
     * Return the provider icon html to link the provider account to the Moodle account.
     * It is display once the user logged in with her/his usual authentication method.
     *
     * @param object $provider
     * @return string
     */
    public function linkaccount($provider) {
        $pagetitle = html_writer::tag('h2',
                get_string('linkaccount', 'auth'),
                array('class' => 'main'));
        $htmlprovider = html_writer::empty_tag('img', array('src' => $provider->logourl));
        $htmlprovider = html_writer::tag('a', $htmlprovider, array('href' => $provider->oauth2client->get_login_url()));
        $htmlprovider = html_writer::tag('div', $htmlprovider, array('class' => 'linkaccount'));
        return $pagetitle . $htmlprovider;
    }

    /**
     * Return form html to choose between create a new user or linking the provider to an existing account.
     *
     * @param object $provider
     * @param string $username
     * @param string $errormsg error
     * @return string
     */
    public function linkingaccountlink($provider, $username, $errormsg = null) {
        $pagetitle = html_writer::tag('h2',
                get_string('doyouhaveanaccount', 'auth'),
                array('class' => 'main'));

        $title = html_writer::tag('h4',
                get_string('newaccount', 'auth'),
                array('class' => ''));
        $htmlprovider = html_writer::empty_tag('img', array('src' => $provider->logourl));
        $htmlprovider = html_writer::tag('a', $htmlprovider, array('href' => $provider->oauth2client->get_login_url()));
        $htmlnoaccount = html_writer::tag('div', $title . $htmlprovider, array('class' => 'confirmnoaccount'));

        $htmlalreadyregistered = $this->loginforms($username,
                array('accounttolink' => $provider->shortname), $errormsg);

        return $pagetitle .
                html_writer::tag('div', $htmlnoaccount . $htmlalreadyregistered,
                        array('class' => 'confirmlinking'));

    }

    /**
     * Return login form html to link the selected provider to an existing account.
     *
     * @param object $provider
     * @param string $username
     * @param string $errormsg error
     * @return string
     */
    public function forceaccountlink($provider, $username, $errormsg = null) {
        $pagetitle = html_writer::tag('h2',
                get_string('oauth2loginrequired', 'auth'),
                array('class' => 'main'));

        $htmlalreadyregistered = $this->loginforms($username,
                array('accounttolink' => $provider->shortname), $errormsg);

        return $pagetitle .
                html_writer::tag('div', $htmlalreadyregistered,
                        array('class' => 'confirmlinking'));

    }

    /**
     * Display all the login forms of enabled auth plugins.
     *
     * @param string $username
     * @param array $additionalparams params to add to the login forms
     * @param string $errormsg error
     * @return string
     */
    private function loginforms($username, $additionalparams, $errormsg = null) {
        global $CFG;

        $loginformshtml = '';

        $auths = get_enabled_auth_plugins();
        foreach ($auths as $auth) {
            // Load auth plugin renderer if exists.
            if (file_exists($CFG->dirroot . '/auth/' . $auth . '/renderer.php')) {
                require_once($CFG->dirroot . '/auth/' . $auth . '/renderer.php');
                $authrendererclass = 'auth_'. $auth .'_renderer';
                $authrenderer = new $authrendererclass($this->page, $this->target);
                $loginformshtml .= $authrenderer->loginform($username, $additionalparams, $errormsg);
            }
        }

        return $loginformshtml;
    }
}