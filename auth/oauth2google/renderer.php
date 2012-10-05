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
 * Renderer for auth_oauth2google plugin
 *
 * @package auth
 * @subpackage oauth2google
 * @copyright  2012 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/pluginlib.php');

/**
 * Standard HTML output renderer for auth_oauth2google
 */
class auth_oauth2google_renderer extends oauth2_plugin_renderer_base {

    /**
     * Documentation
     *
     * @return string
     */
    public function setupdoc() {
        global $CFG;

        $docparams = new stdClass();
        $docparams->link = html_writer::link(new moodle_url("https://code.google.com/apis/console/"),
                get_string('appspage', 'auth_oauth2google'));
        $docparams->redirecturl = html_writer::tag('strong', $CFG->wwwroot.'/admin/oauth2callback.php');
        $parse = parse_url($CFG->wwwroot);
        $docparams->domain = html_writer::tag('strong', $parse['scheme'].'://'.$parse['host']);
        $setup = get_string('auth_oauth2doc', 'auth_oauth2google', $docparams);

        return $setup;
    }
}