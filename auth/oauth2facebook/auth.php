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
 * @copyright 2012 Jerome Mouneyrac
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package auth
 * @subpackage oauth2facebook
 *
 * Authentication Plugin: Facebook Authentication
 * If the email doesn't exist, then the auth plugin creates the user.
 * If the email exist, then the plugin login the user related to this email.
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page.
}

require_once($CFG->libdir . '/oauth2/auth.php');

/**
 * Facebook Oauth2 authentication plugin.
 *
 * @copyright 2012 Jerome Mouneyrac
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
class auth_plugin_oauth2facebook extends auth_plugin_oauth2 {

    /**
     * Constructor.
     */
    public function __construct() {
        global $CFG;

        $this->name = 'Facebook';
        $this->shortname = 'oauth2facebook'; // Required by parent constructor.
        $this->clientlibpath = $CFG->libdir . '/facebookapi.php';
        $this->clientclassname = 'facebook_oauth';

        parent::__construct();
    }
}
