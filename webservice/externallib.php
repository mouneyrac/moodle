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
 * external API for mobile web services
 *
 * @package    core
 * @subpackage webservice
 * @copyright  2011 Moodle Pty Ltd (http://moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class moodle_webservice_mobile_external extends external_api {
    
    public static function get_siteinfo_parameters() {
        return new external_function_parameters(
            array()
        );
    }
    
    /**
     * Return user information including profil picture + basic site information
     * Note:
     * - no validation param because no param
     * - no capability checking because we return just known information by logged user
     * @global type $USER
     * @global type $SITE
     * @global type $CFG
     * @return type 
     */
    function get_siteinfo() {
        global $USER, $SITE, $CFG;
        
        //TODO: if 
        $fs = get_file_storage();
        $usercontext = get_context_instance(CONTEXT_USER, $USER->id);
        $file = $fs->get_file($usercontext->id, 'user', 'icon', 0, '/', 'f1.png');
        if (empty($file)) {
            $profilepicture = file_get_contents($CFG->dirroot . '/pix/u/f1.png');
        } else {
            $profilepicture = $file->get_content();
        }
        
        //TODO: should return a list of available webservice function
        
        return array(
            'sitename' => $SITE->fullname,
            'username' => $USER->username,
            'firstname' => $USER->firstname,
            'lastname' => $USER->lastname,
            'userid' => $USER->id,
            'siteurl' => $CFG->wwwroot,
            // current user's profile picture
            'profilepicture' => base64_encode($profilepicture),
        );
    }
    
    public static function get_siteinfo_returns() {
        return new external_single_structure(
            array(
                'sitename'       => new external_value(PARAM_RAW, 'site name'),
                'username'       => new external_value(PARAM_RAW, 'username'),
                'firstname'       => new external_value(PARAM_TEXT, 'first name'),
                'lastname'       => new external_value(PARAM_TEXT, 'last name'),           
                'userid'       => new external_value(PARAM_INT, 'user id'),
                'siteurl'        => new external_value(PARAM_RAW, 'site url'),
                'profilepicture' => new external_value(PARAM_RAW, 'currently the user profile picture'),
                
            )
        );
    }
}