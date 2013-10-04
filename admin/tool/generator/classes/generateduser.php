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
 * tool_generator generated user.
 *
 * @package tool_generator
 * @copyright 2013 Jerome Mouneyrac
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class tool_generator_generated_user {

    /**
     * @var string Firstname of the user
     */
    public $firstname;

    /**
     * @var string Lastname of the user
     */
    public $lastname;

    /**
     * @var string Username
     */
    public $username;

    /**
     * @var string Email
     */
    public $email;

    /**
     * @var string Picture
     */
    public $picture;

    /**
     * Constructs random user.
     */
    public function __construct($username) {

        if (empty($username)) {
            throw new moodle_exception('user generation: username can not be empty');
        }

        // Retrieve user from randomuser.me.
        $curl = new curl();
        $result = $curl->post("http://api.randomuser.me/");
        $randomusers = json_decode($result);
        $this->firstname = $randomusers->results[0]->user->name->first;
        $this->lastname = $randomusers->results[0]->user->name->last;
        $this->email = $randomusers->results[0]->user->email;

        $this->picture = $randomusers->results[0]->user->picture;

        $this->username = $username;
    }

    public function download_picture($userid) {
        global $CFG, $DB;

        $url = new moodle_url($this->picture);

        // Temporary file name and directories.
        if (empty($CFG->tempdir)) {
            $tempdir = $CFG->dataroot . "/temp";
        } else {
            $tempdir = $CFG->tempdir;
        }
        $picture = $tempdir . '/' . 'generate_user.jpg';

        // Checking if the directory exists (will automatically create it if required).
        // Using this instead of make_temp_directory() ensures backward compatibility with Moodle >= 2.0.
        if (!check_dir_exists($tempdir, true, true)) {
            return;
        }

        require_once($CFG->libdir . '/filelib.php');
        // If there was a problem during the download of the picture, cancel the operation.
        if (!download_file_content($url->out(false), null, null, false, 5, 2, false, $picture)) {
            return;
        }


        // Ensures retro compatibility.
        $context = context_user::instance($userid);


        require_once($CFG->libdir . '/gdlib.php');
        $id = process_new_icon($context, 'user', 'icon', 0, $picture);
        $DB->set_field('user', 'picture', $id, array('id' => $userid));
    }
}