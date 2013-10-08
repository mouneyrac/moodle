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
 * Process concurrent ajax request.
 * ALL RETURNED INFO IS PUBLIC.
 *
 * @package assignfeedback_editpdf
 * @copyright  2013 Jerome Mouneyrac
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
// To be able to process concurrent ajax request with the generate pdf ajax request we to not use cookie.
define('NO_MOODLE_COOKIES', true);

require_once('../../../../config.php');

$action = optional_param('action', '', PARAM_ALPHANUM);
$assignmentid = required_param('assignmentid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$attemptnumber = required_param('attemptnumber', PARAM_INT);

// Only return the number of images been writing in the temp folder.
$imagedir = $CFG->dataroot . '/temp/assignfeedback_editpdf/pageimages/'
    . sha1($assignmentid . '_' . $userid . '_' . $attemptnumber);
$filetotal = 0;
if ($handle = opendir($imagedir)) {
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != "..") {
            $filetotal += 1;
        }
    }
    closedir($handle);
}

echo json_encode($filetotal);