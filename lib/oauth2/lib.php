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
 * @package auth_oauth2
 *
 * Authentication Plugin: Google/Facebook/Messenger Authentication
 * If the email doesn't exist, then the auth plugin creates the user.
 * If the email exist (and the user has for auth plugin this current one),
 * then the plugin login the user related to this email.
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

/**
 * Oauth2 authentication plugin manager
 */
class auth_oauth2_manager {

    public $providers;
    
    /**
     * Load the providers.
     *
     * It's hardcoded here as we don't need to change them.
     * Only clientid/secretid is saved in the DB by the auth plugin.
     */
    public function __construct() {
        global $CFG;

        //look to all provider into the folder
        $this->providers = array();

        if ($handle = opendir($CFG->dirroot . '/auth')) {

            // Retrieve all providers (must be in files provider_SHORTNAME.php)
            while (false !== ($entry = readdir($handle))) {

                if (strpos($entry, 'oauth2') === 0) {
                    require_once($CFG->dirroot . '/auth/'.$entry.'/auth.php');
                    $providerclass = 'auth_plugin_'.$entry;
                    $provider = new $providerclass();
                    $this->providers['auth_'.$provider->shortname] = $provider;
                }
            }

            closedir($handle);
        }
    }

    /**
     *  Logos box - for the login page / linking profile.
     */
    public function getlinkableproviders($profilelinking = false) {
        global $CFG, $USER, $DB;

        // Retrieve linked providers if user is logged.
        $linkeddbproviders = array();
        if (isloggedin()) {
            $linkeddbproviders = $DB->get_records_sql('SELECT component FROM {user_auths}
            WHERE userid = :userid GROUP BY component', array('userid' => $USER->id));
        }

//        //get previous auth provider
//        $allauthproviders = optional_param('allauthproviders', false, PARAM_BOOL);
//        $cookiename = 'MOODLEGOOGLEOAUTH2_'.$CFG->sessioncookie;
//        if (empty($_COOKIE[$cookiename])) {
//            $authprovider = '';
//        } else {
//            $authprovider = $_COOKIE[$cookiename];
//        }

        $linkableproviders = array();
        foreach ($this->providers as $provider) {

            // Add profile linking params to return url if we don't want to login but associate
            // a social account to the Moodle account.
            if ($profilelinking) {
                $provider->oauth2client->returnurl->param('profilelinking', $profilelinking);
                $provider->oauth2client->returnurl->param('sesskey', sesskey());
            }

            //Display the provider if the provider account is setup and if
            //either the provider was the last selected provider, or either there were no previously selected providers,
            //or either user wanted to see all providers
            if (is_enabled_auth($provider->shortname) &&
                    (empty($authprovider) || $authprovider == $provider->shortname || $allauthproviders)) {

                // Mark provider as linked
                $provider->linked = false;
                foreach ($linkeddbproviders as $linkeddbprovider) {
                    if ($linkeddbprovider->component == 'auth_'.$provider->shortname) {
                        $provider->linked = true;
                    }
                }

                $linkableproviders[] = $provider;

            }
        }

        // Return providers
        return $linkableproviders;
    }

}