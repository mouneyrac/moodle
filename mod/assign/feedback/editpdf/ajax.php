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
 * Process ajax requests
 *
 * @package assignfeedback_editpdf
 * @copyright  2012 Davo Smith
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('AJAX_SCRIPT')) {
    define('AJAX_SCRIPT', true);
}

require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

require_sesskey();

$action = optional_param('action', '', PARAM_ALPHANUM);
$assignmentid = required_param('assignmentid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$attemptnumber = required_param('attemptnumber', PARAM_INT);

$cm = \get_coursemodule_from_instance('assign', $assignmentid, 0, false, MUST_EXIST);
$context = \context_module::instance($cm->id);

$assignment = new \assign($context, null, null);

if (!$assignment->can_view_submission($userid)) {
    print_error('nopermission');
}

if ($action == 'loadallpages') {

    $pages = \assignfeedback_editpdf\ingest::get_page_images_for_attempt($assignment,
                                                                         $userid,
                                                                         $attemptnumber);

    $response = new stdClass();
    $response->pagecount = count($pages);
    $response->pages = array();

    $grade = $assignment->get_user_grade($userid, true);

    foreach ($pages as $id => $pagefile) {
        $index = count($response->pages);
        $page = new stdClass();
        $comments = \assignfeedback_editpdf\page_editor::get_comments($grade->id, $index);
        $page->url = moodle_url::make_pluginfile_url($context->id,
                                                     'assignfeedback_editpdf',
                                                     \assignfeedback_editpdf\ingest::PAGE_IMAGE_FILEAREA,
                                                     $grade->id,
                                                     '/',
                                                     $pagefile->get_filename())->out();
        $page->comments = $comments;
        $annotations = \assignfeedback_editpdf\page_editor::get_annotations($grade->id, $index);
        $page->annotations = $annotations;
        array_push($response->pages, $page);
    }

    echo json_encode($response);
    die();
} else if ($action == 'saveallpages') {
    require_capability('mod/assign:grade', $context);

    $response = new stdClass();
    $response->errors = array();

    $grade = $assignment->get_user_grade($userid, true);

    $pagesjson = required_param('pages', PARAM_RAW);
    $pages = json_decode($pagesjson);

    foreach ($pages as $index => $page) {
        $added = \assignfeedback_editpdf\page_editor::set_comments($grade->id, $index, $page->comments);
        if ($added != count($page->comments)) {
            array_push($response->errors, get_string('couldnotsavepage', 'assignfeedback_editpdf', $index+1));
        }
        $added = \assignfeedback_editpdf\page_editor::set_annotations($grade->id, $index, $page->annotations);
        if ($added != count($page->annotations)) {
            array_push($response->errors, get_string('couldnotsavepage', 'assignfeedback_editpdf', $index+1));
        }
    }

    echo json_encode($response);
    die();

}

