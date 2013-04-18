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
 * Renderer for auth_oauth2facebook plugin
 *
 * @package auth
 * @subpackage oauth2facebook
 * @copyright  2012 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/pluginlib.php');

/**
 * Standard HTML output renderer for auth_oauth2facebook
 */
class auth_oauth2facebook_renderer extends oauth2_plugin_renderer_base {

    /**
     * Documentation
     *
     * @return string
     */
    public function setupdoc() {
        global $CFG;

        $docparams = new stdClass();
        $docparams->link = html_writer::link(new moodle_url("https://developers.facebook.com/apps/"),
                get_string('appspage', 'auth_oauth2facebook'));
        $docparams->siteurl = html_writer::tag('strong', $CFG->wwwroot);
        $parse = parse_url($CFG->wwwroot);
        $docparams->domain = html_writer::tag('strong', $parse['scheme'].'://'.$parse['host']);
        $setup = get_string('auth_oauth2doc', 'auth_oauth2facebook', $docparams);

        return $setup;
    }
}