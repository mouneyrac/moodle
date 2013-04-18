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
 * Oauth2 auth plugin library
 *
 * @copyright 2012 Jerome Mouneyrac
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package core_auth
 *
 * Authentication Plugin: Google/Facebook/Messenger Authentication
 * If the email doesn't exist, then the auth plugin creates the user.
 * If the email exist (and the user has for auth plugin this current one),
 * then the plugin login the user related to this email.
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

/**
 * Oauth2 authentication plugin manager
 *
 * @copyright 2012 Jerome Mouneyrac
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
class auth_oauth2_manager {

    /** @var array the linkable providers */
    public $providers;

    /**
     * Load the providers.
     *
     * It's hardcoded here as we don't need to change them.
     * Only clientid/secretid is saved in the DB by the auth plugin.
     */
    public function __construct() {
        // Look to all provider into the folder.
        $this->providers = array();

        $enabledauthplugins = get_enabled_auth_plugins();
        foreach ($enabledauthplugins as $enabledauthplugin) {
            $provider = get_auth_plugin($enabledauthplugin);
            if ($provider instanceof auth_plugin_oauth2) {
                $this->providers['auth_'.$provider->shortname] = $provider;
            }
        }
    }

    /**
     * Logos box - for the login page / linking profile.
     *
     * @param boolean $profilelinking - if true additional linking info are added to the providers.
     * @return array of linkable providers
     */
    public function get_linkable_providers($profilelinking = false) {
        global $USER, $DB;

        // Retrieve linked providers if user is logged.
        $linkeddbproviders = array();
        if (!isguestuser() and isloggedin()) {
            $linkeddbproviders = $DB->get_records_sql('SELECT component FROM {user_idps}
            WHERE userid = :userid GROUP BY component', array('userid' => $USER->id));
        }

        $linkableproviders = array();
        foreach ($this->providers as $provider) {

            // Add profile linking params to return url if we don't want to login.
            // But associate a social account to the Moodle account.
            if ($profilelinking) {
                $provider->oauth2client->returnurl->param('profilelinking', $profilelinking);
                $provider->oauth2client->returnurl->param('sesskey', sesskey());
            }

            // Mark provider as linked
            $provider->linked = array_key_exists('auth_'.$provider->shortname, $linkeddbproviders);

            $linkableproviders[] = $provider;
        }

        // Return providers
        return $linkableproviders;
    }

}
