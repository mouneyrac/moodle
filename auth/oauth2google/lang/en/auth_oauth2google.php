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
 * Strings for component 'auth_oauth2google', language 'en'
 *
 * @package auth
 * @subpackage oauth2google
 * @copyright 2012 Jerome Mouneyrac
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Google Oauth2';
$string['auth_clientid_key'] = 'Client ID';
$string['auth_clientsecret_key'] = 'Client secret';
$string['auth_oauth2googledescription'] = 'Simple and straight forward Oauth2 Google authentication.';
$string['auth_oauth2doc'] = '
1) Go to {$a->link}.<br/>
2) You might setup your branding the first time you access the Google API console. Then create a client ID. Select "Web Application". Your redirect URI is {$a->redirecturl}. Your authorized Javascript origin is {$a->domain}.<br/>
3) Copy the generated Client ID and Client secret below and save.
';
$string['appspage'] = 'your Google API console';