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
 * Simple implementation of some Facebook API functions for Moodle.
 *
 * @package   core
 * @copyright 2012 Jerome Mouneyrac
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/oauthlib.php');

/**
 * OAuth 2.0 client for Facebook Services
 *
 * @package   core
 * @copyright 2012 Jerome Mouneyrac
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class facebook_oauth extends oauth2_auth_plugin_client {

    /** @var string default scope of the authentication request */
    protected $authscope = 'email';

    /**
     * Returns the auth url for OAuth 2.0 request
     * @return string the auth url
     */
    protected function auth_url() {
        return 'https://www.facebook.com/dialog/oauth';
    }

    /**
     * Returns the token url for OAuth 2.0 request
     * @return string the auth url
     */
    protected function token_url() {
        return 'https://graph.facebook.com/oauth/access_token';
    }

    /**
     * Resets headers and response for multiple requests
     */
    public function reset_state() {
        $this->header = array();
        $this->response = array();
    }

    /**
     * HTTP GET should be used instead of POST.
     *
     * @return bool true if GET should be used
     */
    protected function use_http_get() {
        return true;
    }

    /**
     * Decode access token result return by the access token request
     * Expected
     *
     * @param string $response HTTP result
     * @return object must contain "access_key" and "expires_in"
     */
    protected function decode_access_token_result($response) {
        parse_str($response, $returnvalues);
        $accesstoken = new stdClass();
        $accesstoken->access_token = $returnvalues['access_token'];
        $accesstoken->expires_in = $returnvalues['expires'];
        return $accesstoken;
    }

    /**
     * The most common API call to get the authenticated user info.
     * Fill up (oauth2user) $this->oauth2user with the retrieved information.
     */
    public function retrieve_auth_user_info() {
        if (empty($this->oauth2user->email)) {
            $postreturnvalues = $this->get('https://graph.facebook.com/me');
            $facebookuser = json_decode($postreturnvalues);

            if (!empty($facebookuser->first_name)) {
                $this->oauth2user->firstname = $facebookuser->first_name;
            }
            if (!empty($facebookuser->last_name)) {
                $this->oauth2user->lastname = $facebookuser->last_name;
            }

            $this->oauth2user->id = $facebookuser->id;
            $this->oauth2user->email = $facebookuser->email;
            $this->oauth2user->name = $facebookuser->name;
            $this->oauth2user->verified = $facebookuser->verified;
            $this->oauth2user->link = $facebookuser->link;
        }
    }
}